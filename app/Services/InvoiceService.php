<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\Merchant;
use App\Models\PaymentInvoice;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly WebhookService $webhookService,
    ) {}

    /** Validate merchant can create an invoice in the given currency/amount. */
    public function validateCreate(Merchant $merchant, Currency $currency, string|float $amount): void
    {
        $errors = [];

        if (! $merchant->featuresUnlocked()) {
            $errors['merchant'] = ['Merchant is not approved. API access requires active status.'];
        }

        if (! $merchant->is_active) {
            $errors['merchant'] = ['Merchant account is disabled.'];
        }

        $currencyEnabled = $merchant->currencies()
            ->where('currencies.id', $currency->id)
            ->wherePivot('is_enabled', true)
            ->exists();

        if (! $currencyEnabled) {
            $errors['currency'] = ["Currency {$currency->code} is not enabled for this merchant."];
        }

        $amountStr = $this->normalizeAmount($amount);

        if ($merchant->max_invoice_amount !== null && bccomp($amountStr, (string) $merchant->max_invoice_amount, 18) > 0) {
            $errors['amount'] = ['Amount exceeds merchant max invoice limit.'];
        }

        if ($merchant->daily_turnover_limit !== null) {
            $today = $this->merchantTurnoverSince($merchant, now()->startOfDay());
            $projected = bcadd($today, $amountStr, 18);
            if (bccomp($projected, (string) $merchant->daily_turnover_limit, 18) > 0) {
                $errors['amount'] = ['Daily turnover limit would be exceeded.'];
            }
        }

        if ($merchant->monthly_turnover_limit !== null) {
            $month = $this->merchantTurnoverSince($merchant, now()->startOfMonth());
            $projected = bcadd($month, $amountStr, 18);
            if (bccomp($projected, (string) $merchant->monthly_turnover_limit, 18) > 0) {
                $errors['amount'] = ['Monthly turnover limit would be exceeded.'];
            }
        }

        $globalMaxUsd = (float) Setting::get('max_invoice_amount_usd', 0);
        if ($globalMaxUsd > 0) {
            $rateUsd = $this->resolveUsdRate($currency, $amountStr);
            if ($rateUsd !== null && $rateUsd > $globalMaxUsd) {
                $errors['amount'] = ['Amount exceeds platform max invoice limit (USD).'];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function create(Merchant $merchant, array $data): PaymentInvoice
    {
        /** @var Currency $currency */
        $currency = Currency::where('code', $data['currency'])->where('is_active', true)->firstOrFail();

        $this->validateCreate($merchant, $currency, $data['amount']);

        $invoice = DB::transaction(function () use ($merchant, $currency, $data): PaymentInvoice {
            $wallet = $this->walletService->assignDepositWallet($currency, $merchant);

            $ttl = isset($data['expires_in']) && $data['expires_in'] > 0
                ? (int) $data['expires_in']
                : (int) Setting::get('invoice_ttl_minutes', config('crynova.invoice_ttl_minutes', 30));

            $metadata = $data['metadata'] ?? [];
            if ($merchant->test_mode) {
                $metadata['test_mode'] = true;
            }

            $invoice = PaymentInvoice::create([
                'merchant_id' => $merchant->id,
                'currency_id' => $currency->id,
                'order_id'    => $data['order_id'] ?? null,
                'description' => $data['description'] ?? null,
                'amount'      => $data['amount'],
                'pay_address' => $wallet->address,
                'pay_memo'    => $wallet->memo,
                'status'      => 'pending',
                'fee_percent' => $merchant->fee_percent,
                'rate_usd'    => $data['rate_usd'] ?? $this->resolveUsdRate($currency, (string) $data['amount']),
                'expires_at'  => now()->addMinutes($ttl),
                'metadata'    => $metadata ?: null,
            ]);

            $wallet->update(['invoice_id' => $invoice->id, 'is_used' => true]);

            AuditLog::record('invoice.created', $invoice, [], $invoice->toArray(), 'api');

            return $invoice->load('currency', 'merchant');
        });

        // Notify the merchant endpoint after the response is sent (no added latency).
        $webhook = $this->webhookService;
        app()->terminating(function () use ($webhook, $invoice) {
            $webhook->dispatch($invoice->refresh()->load('currency', 'merchant'), 'invoice.created');
        });

        return $invoice;
    }

    private function merchantTurnoverSince(Merchant $merchant, \Carbon\Carbon $since): string
    {
        $sum = $merchant->invoices()
            ->whereIn('status', ['paid', 'overpaid'])
            ->where('paid_at', '>=', $since)
            ->sum('amount');

        return (string) ($sum ?: '0');
    }

    private function resolveUsdRate(Currency $currency, string $amount): ?float
    {
        // Placeholder until rate oracle is wired — use amount as USD equivalent for stablecoins.
        if (str_contains($currency->code, 'USDT') || str_contains($currency->code, 'USD')) {
            return (float) $amount;
        }

        return null;
    }

    private function normalizeAmount(string|float $amount): string
    {
        return is_string($amount) ? $amount : sprintf('%.18F', $amount);
    }
}
