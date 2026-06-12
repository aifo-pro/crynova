<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BalanceMovement;
use App\Models\BlockchainTransaction;
use App\Models\PaymentInvoice;
use App\Services\Blockchain\BlockchainDriverFactory;
use App\Services\Blockchain\TestBlockchainDriver;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentCheckerService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly EmailService $emailService,
        private readonly TelegramNotificationService $telegram,
        private readonly BlockchainDriverFactory $driverFactory,
        private readonly WebhookService $webhooks,
        private readonly BalanceService $balances,
    ) {}

    /** Queue a webhook to fire once the surrounding DB transaction commits. */
    private function queueWebhook(PaymentInvoice $invoice, string $event): void
    {
        $id = $invoice->id;
        DB::afterCommit(function () use ($id, $event) {
            $fresh = PaymentInvoice::with('currency', 'merchant')->find($id);
            if ($fresh) {
                $this->webhooks->dispatch($fresh, $event);
            }
        });
    }

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
            (string) ($amount ?? $invoice->payableAmount()),
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
            $lockedInvoice = PaymentInvoice::whereKey($invoice->id)->lockForUpdate()->first();
            if (! $lockedInvoice || $lockedInvoice->isFinal()) {
                return;
            }

            $tx = BlockchainTransaction::firstOrCreate(
                ['tx_hash' => $txHash, 'currency_id' => $lockedInvoice->currency_id],
                [
                    'invoice_id'              => $lockedInvoice->id,
                    'from_address'            => $txData['from'] ?? null,
                    'to_address'              => $lockedInvoice->pay_address,
                    'amount'                  => $txData['amount'] ?? 0,
                    'fee'                     => $txData['fee'] ?? 0,
                    'confirmations'           => $txData['confirmations'] ?? 0,
                    'confirmations_required'  => $lockedInvoice->currency->confirmations_required,
                    'direction'               => 'incoming',
                    'status'                  => 'pending',
                    'block_number'            => $txData['blockindex'] ?? null,
                    'block_time'              => isset($txData['blocktime'])
                        ? \Carbon\Carbon::createFromTimestamp($txData['blocktime'])
                        : null,
                    'raw_data'                => array_diff_key($txData, ['raw_tx' => '']),
                ],
            );

            if ($tx->confirmations < ($txData['confirmations'] ?? 0)) {
                $tx->update(['confirmations' => $txData['confirmations']]);
            }

            $this->updateInvoiceStatus($lockedInvoice);
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
                $this->queueWebhook($invoice, 'invoice.waiting_confirmations');
            } else {
                $invoice->update(['amount_received' => $received]);
            }

            return;
        }

        $incoming->each(fn (BlockchainTransaction $t) => $t->update(['status' => 'confirmed']));

        $expected  = $invoice->payableAmount();
        $diff      = bcsub($received, $expected, 18);
        $threshold = '0.000001';

        $merchant   = $invoice->merchant;
        $shortfall  = bccomp($diff, '0', 18) < 0 ? bcsub('0', $diff, 18) : '0';
        $allowance  = $this->partialAllowance($merchant, $expected);
        $withinTol  = bccomp($shortfall, '0', 18) > 0 && bccomp($shortfall, $allowance, 18) <= 0;

        $newStatus = match (true) {
            bccomp($diff, $threshold, 18) > 0            => 'overpaid',
            bccomp($diff, '-' . $threshold, 18) >= 0     => 'paid',
            $withinTol                                     => 'paid',
            default                                        => 'underpaid',
        };

        $feeAmount = bcmul($received, bcdiv((string) $invoice->fee_percent, '100', 18), 18);
        $netAmount = ($merchant->service_fee_payer ?? 'merchant') === 'client'
            ? $received
            : bcsub($received, $feeAmount, 18);

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
        $this->queueWebhook($invoice, "invoice.{$newStatus}");

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
        $idempotencyKey = 'invoice:' . $invoice->id . ':settlement';

        if (BalanceMovement::where('idempotency_key', $idempotencyKey)->exists()) {
            return;
        }

        $merchant = $invoice->merchant;
        $balance  = $this->balances->forMerchant($merchant, $invoice->currency, lock: true);
        $amlHold  = (bool) ($merchant->aml_enabled ?? false);

        $note = "Invoice {$invoice->uuid} paid";
        if ($amlHold) {
            $note .= ' · AML hold';
        }
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

        try {
            BalanceMovement::create([
                'merchant_id'      => $invoice->merchant_id,
                'currency_id'      => $invoice->currency_id,
                'movable_id'       => $invoice->id,
                'movable_type'     => PaymentInvoice::class,
                'type'             => $amlHold ? 'hold' : 'credit',
                'idempotency_key'  => $idempotencyKey,
                'amount'           => $netAmount,
                'balance_before'   => $before,
                'balance_after'    => $after,
                'note'             => $note,
            ]);
        } catch (QueryException $e) {
            if (! $this->isDuplicateKey($e)) {
                throw $e;
            }
        }
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        $code = (string) $e->getCode();

        return str_contains($e->getMessage(), 'idempotency_key')
            || $code === '23000'
            || $code === '23505';
    }

    /** Allowed partial-payment shortfall for a merchant, in the invoice currency. */
    private function partialAllowance(\App\Models\Merchant $merchant, string $expected): string
    {
        $value = (string) ($merchant->partial_confirm_value ?? '0');

        if (bccomp($value, '0', 18) <= 0) {
            return '0';
        }

        return ($merchant->partial_confirm_unit ?? 'fixed') === 'percent'
            ? bcmul($expected, bcdiv($value, '100', 18), 18)
            : $value;
    }

    private function markExpired(PaymentInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $locked = PaymentInvoice::whereKey($invoice->id)->lockForUpdate()->first();
            if (! $locked || $locked->isFinal()) {
                return;
            }

            $locked->update(['status' => 'expired']);
            AuditLog::record('invoice.expired', $locked, [], [], 'system');
            $this->queueWebhook($locked, 'invoice.expired');
        });
    }
}
