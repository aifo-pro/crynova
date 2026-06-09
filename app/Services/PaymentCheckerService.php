<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Balance;
use App\Models\BalanceMovement;
use App\Models\BlockchainTransaction;
use App\Models\PaymentInvoice;
use App\Services\Blockchain\BlockchainDriverFactory;
use App\Services\Blockchain\TestBlockchainDriver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentCheckerService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly EmailService $emailService,
        private readonly TelegramNotificationService $telegram,
        private readonly BlockchainDriverFactory $driverFactory,
    ) {}

    // Called by the queue worker for each pending invoice
    public function check(PaymentInvoice $invoice): void
    {
        if ($invoice->isFinal()) {
            return;
        }

        if ($invoice->isExpired()) {
            $this->markExpired($invoice);

            return;
        }

        try {
            $driver = $this->driverFactory->forCurrency($invoice->currency, $invoice->merchant);
            $txs    = $driver->getTransactions($invoice->pay_address, 0, $invoice->currency);

            foreach ($txs as $txData) {
                $this->processTx($invoice, $txData);
            }
        } catch (\Throwable $e) {
            Log::error("PaymentChecker failed for invoice {$invoice->uuid}: " . $e->getMessage());
        }
    }

    /** Simulate payment for test_mode merchants (sandbox). */
    public function simulateTestPayment(PaymentInvoice $invoice, ?string $amount = null): void
    {
        abort_unless($invoice->merchant->test_mode, 422, 'Only test_mode merchants support simulated payments.');

        TestBlockchainDriver::queuePayment(
            $invoice->pay_address,
            (string) ($amount ?? $invoice->amount),
            max($invoice->currency->confirmations_required, 1),
        );

        $this->check($invoice->fresh(['currency', 'merchant', 'transactions']));
    }

    private function processTx(PaymentInvoice $invoice, array $txData): void
    {
        $txHash = $txData['txid'] ?? $txData['tx_hash'] ?? null;

        if (! $txHash) {
            return;
        }

        DB::transaction(function () use ($invoice, $txData, $txHash) {
            $tx = BlockchainTransaction::firstOrCreate(
                ['tx_hash' => $txHash, 'currency_id' => $invoice->currency_id],
                [
                    'invoice_id'              => $invoice->id,
                    'from_address'            => $txData['from'] ?? null,
                    'to_address'              => $invoice->pay_address,
                    'amount'                  => $txData['amount'] ?? 0,
                    'fee'                     => $txData['fee'] ?? 0,
                    'confirmations'           => $txData['confirmations'] ?? 0,
                    'confirmations_required'  => $invoice->currency->confirmations_required,
                    'direction'               => 'incoming',
                    'status'                  => 'pending',
                    'block_number'            => $txData['blockindex'] ?? null,
                    'block_time'              => isset($txData['blocktime'])
                        ? \Carbon\Carbon::createFromTimestamp($txData['blocktime'])
                        : null,
                    'raw_data'                => array_diff_key($txData, ['raw_tx' => '']),
                ],
            );

            // Update confirmation count on subsequent checks
            if ($tx->confirmations < ($txData['confirmations'] ?? 0)) {
                $tx->update(['confirmations' => $txData['confirmations']]);
            }

                $this->updateInvoiceStatus($invoice);
        });
    }

    private function updateInvoiceStatus(PaymentInvoice $invoice): void
    {
        $invoice->refresh();

        if ($invoice->isFinal()) {
            return;
        }

        $required  = $invoice->currency->confirmations_required;
        $incoming  = $invoice->transactions()->where('direction', 'incoming')->get();

        if ($incoming->isEmpty()) {
            return;
        }

        $received = (string) $incoming->sum('amount');

        $allConfirmed = $incoming->every(
            fn (BlockchainTransaction $t) => $t->confirmations >= $required
        );

        if (! $allConfirmed) {
            if ($invoice->status === 'pending') {
                $invoice->update(['status' => 'waiting_confirmations', 'amount_received' => $received]);
            } else {
                $invoice->update(['amount_received' => $received]);
            }

            return;
        }

        // All incoming transactions confirmed
        $incoming->each(fn (BlockchainTransaction $t) => $t->update(['status' => 'confirmed']));

        $expected  = (string) $invoice->amount;
        $diff      = bcsub($received, $expected, 18);
        $threshold = '0.000001';

        // Merchant-configured auto-confirm tolerance for partial payments:
        // if the shortfall is within the allowed deviation, accept as paid.
        $merchant   = $invoice->merchant;
        $shortfall  = bccomp($diff, '0', 18) < 0 ? bcsub('0', $diff, 18) : '0';
        $allowance  = $this->partialAllowance($merchant, $expected);
        $withinTol  = bccomp($shortfall, '0', 18) > 0 && bccomp($shortfall, $allowance, 18) <= 0;

        $newStatus = match (true) {
            bccomp($diff, $threshold, 18) > 0            => 'overpaid',
            bccomp($diff, '-' . $threshold, 18) >= 0     => 'paid',
            $withinTol                                     => 'paid', // partial within tolerance
            default                                        => 'underpaid',
        };

        // Service fee: taken by the platform. If the client pays the fee it was
        // already added on top of the price, so the merchant receives the full
        // amount; if the merchant pays, the fee is deducted from the credit.
        $feeAmount = bcmul($received, bcdiv((string) $invoice->fee_percent, '100', 18), 18);
        $netAmount = ($merchant->service_fee_payer ?? 'merchant') === 'client'
            ? $received                       // client already covered the fee
            : bcsub($received, $feeAmount, 18); // merchant absorbs the fee

        $invoice->update([
            'status'          => $newStatus,
            'amount_received' => $received,
            'fee_amount'      => $feeAmount,
            'net_amount'      => $netAmount,
            'paid_at'         => now(),
        ]);

        if (in_array($newStatus, ['paid', 'overpaid'], true)) {
            $this->creditMerchant($invoice, $netAmount);
        }

        AuditLog::record("invoice.{$newStatus}", $invoice, [], [], 'system');

        if (in_array($newStatus, ['paid', 'overpaid'], true)) {
            DB::afterCommit(function () use ($invoice) {
                $freshInvoice = PaymentInvoice::with('merchant.user', 'currency', 'transactions')->find($invoice->id);
                if ($freshInvoice) {
                    $this->emailService->sendPaymentReceipt($freshInvoice);
                    $this->telegram->notifyInvoicePaid($freshInvoice);
                }
            });
        }
    }

    private function creditMerchant(PaymentInvoice $invoice, string $netAmount): void
    {
        $alreadyCredited = BalanceMovement::where('movable_type', PaymentInvoice::class)
            ->where('movable_id', $invoice->id)
            ->where('type', 'credit')
            ->exists();

        if ($alreadyCredited) {
            return;
        }

        $merchant = $invoice->merchant;
        $balance  = $merchant->balanceFor($invoice->currency);

        // AML screening: when enabled, funds land in a hold (locked) instead of
        // available, pending manual/automatic review before settlement.
        $amlHold = (bool) ($merchant->aml_enabled ?? false);

        $note = "Invoice {$invoice->uuid} paid";
        if ($amlHold) {
            $note .= ' · AML hold';
        }
        // Auto-conversion intent (actual swap performed by the settlement layer).
        if (($merchant->autoconvert_enabled ?? false) && $merchant->autoconvert_target_currency_id) {
            $note .= ' · auto-convert → currency#' . $merchant->autoconvert_target_currency_id;
        }

        if ($amlHold) {
            $before = $balance->locked;
            $after  = bcadd((string) $before, $netAmount, 18);
            $balance->update(['locked' => $after]);
        } else {
            $before = $balance->available;
            $after  = bcadd((string) $before, $netAmount, 18);
            $balance->update(['available' => $after]);
        }

        BalanceMovement::create([
            'merchant_id'    => $invoice->merchant_id,
            'currency_id'    => $invoice->currency_id,
            'movable_id'     => $invoice->id,
            'movable_type'   => PaymentInvoice::class,
            'type'           => $amlHold ? 'hold' : 'credit',
            'amount'         => $netAmount,
            'balance_before' => $before,
            'balance_after'  => $after,
            'note'           => $note,
        ]);
    }

    /** Allowed partial-payment shortfall for a merchant, in the invoice currency. */
    private function partialAllowance(\App\Models\Merchant $merchant, string $expected): string
    {
        $value = (string) ($merchant->partial_confirm_value ?? '0');

        if (bccomp($value, '0', 18) <= 0) {
            return '0';
        }

        // Percent of the expected amount, or a fixed absolute amount.
        return ($merchant->partial_confirm_unit ?? 'fixed') === 'percent'
            ? bcmul($expected, bcdiv($value, '100', 18), 18)
            : $value;
    }

    private function markExpired(PaymentInvoice $invoice): void
    {
        $invoice->update(['status' => 'expired']);
        AuditLog::record('invoice.expired', $invoice, [], [], 'system');
    }

}
