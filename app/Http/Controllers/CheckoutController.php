<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\PaymentInvoice;
use App\Models\PaymentLink;
use App\Services\InvoiceService;
use App\Services\RateService;
use App\Services\WalletService;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    // GET /pay/{uuid}
    public function show(string $uuid, WebhookService $webhooks, RateService $rates): View|\Illuminate\Http\RedirectResponse
    {
        $invoice = PaymentInvoice::with('currency', 'merchant', 'transactions')
            ->where('uuid', $uuid)
            ->firstOrFail();

        $this->expireIfOverdue($invoice, $webhooks);

        if ($invoice->isFinal() && $invoice->status !== 'waiting_confirmations') {
            return view('checkout.final', compact('invoice'));
        }

        // Fiat-priced invoice — customer must pick a crypto first.
        if ($invoice->needsCurrencySelection()) {
            return view('checkout.select-currency', [
                'invoice' => $invoice,
                'options' => $this->currencyOptions($invoice, $rates),
            ]);
        }

        // QR code data URI (address or BIP-21 URI)
        $qrData = $this->buildQrUri($invoice);

        return view('checkout.show', compact('invoice', 'qrData'));
    }

    // GET /pay/{uuid}/status  — polled by JS every 5s
    public function status(string $uuid, WebhookService $webhooks): \Illuminate\Http\JsonResponse
    {
        $invoice = PaymentInvoice::with('currency', 'transactions', 'merchant')
            ->where('uuid', $uuid)
            ->firstOrFail();

        $this->expireIfOverdue($invoice, $webhooks);

        return response()->json([
            'status'          => $invoice->status,
            'amount_received' => (string) $invoice->amount_received,
            'confirmations'   => (int) $invoice->transactions->max('confirmations'),
            'confirmations_required' => (int) optional($invoice->currency)->confirmations_required,
            'expires_in'      => $invoice->expires_at
                ? max(0, $invoice->expires_at->diffInSeconds(now(), false) * -1)
                : null,
            'is_final'        => $invoice->isFinal(),
        ]);
    }

    // GET /pay/link/{token}  — public payment link landing page
    public function paymentLink(string $token): View|\Illuminate\Http\RedirectResponse
    {
        $link = PaymentLink::with('currency', 'merchant')
            ->where('token', $token)
            ->firstOrFail();

        if (! $link->isAvailable()) {
            abort(404, 'This payment link is no longer available.');
        }

        $currencies = Currency::where('is_active', true)->orderBy('code')->get();

        return view('checkout.payment-link', compact('link', 'currencies'));
    }

    // POST /pay/link/{token}  — create invoice from payment link and redirect to checkout
    public function paymentLinkCreate(Request $request, string $token): \Illuminate\Http\RedirectResponse
    {
        $link = PaymentLink::with('currency', 'merchant')
            ->where('token', $token)
            ->firstOrFail();

        if (! $link->isAvailable()) {
            abort(404, 'This payment link is no longer available.');
        }

        $request->validate([
            'amount'      => $link->amount ? [] : ['required', 'numeric', 'gt:0'],
            'currency_id' => $link->currency_id ? [] : ['required', 'integer', 'exists:currencies,id'],
        ]);

        $currencyCode = $link->currency
            ? $link->currency->code
            : Currency::findOrFail($request->input('currency_id'))->code;

        $invoiceService = app(InvoiceService::class);
        $invoice = $invoiceService->create($link->merchant, [
            'currency'        => $currencyCode,
            'amount'          => $link->amount ?? $request->input('amount'),
            'order_id'        => $link->order_id_prefix
                ? $link->order_id_prefix . now()->format('YmdHis')
                : null,
            'metadata'        => ['payment_link_token' => $token],
        ]);

        // Increment use counter atomically
        $link->increment('use_count');

        return redirect()->route('checkout.show', $invoice->uuid);
    }

    // GET /pay/pos/{shop}  — hosted per-merchant payment/donation page
    public function pos(string $shop): View
    {
        $merchant = \App\Models\Merchant::where('shop_id', $shop)->firstOrFail();

        // Only active merchants can collect via the hosted page
        abort_unless($merchant->featuresUnlocked(), 404, 'This payment page is not available.');

        $currencies = $merchant->currencies()->wherePivot('is_enabled', true)->get();
        if ($currencies->isEmpty()) {
            $currencies = Currency::where('is_active', true)->orderBy('code')->get();
        }

        return view('checkout.pos', compact('merchant', 'currencies'));
    }

    // POST /pay/pos/{shop}  — create invoice from the hosted page
    public function posCreate(Request $request, string $shop): \Illuminate\Http\RedirectResponse
    {
        $merchant = \App\Models\Merchant::where('shop_id', $shop)->firstOrFail();
        abort_unless($merchant->featuresUnlocked(), 404);

        $request->validate([
            'amount'      => ['required', 'numeric', 'gt:0'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
        ]);

        $currency = Currency::findOrFail($request->input('currency_id'));

        try {
            $invoice = app(InvoiceService::class)->create($merchant, [
                'currency' => $currency->code,
                'amount'   => $request->input('amount'),
                'metadata' => ['source' => 'pos', 'shop_id' => $shop],
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', __('flash.checkout_currency_unavailable'));
        }

        return redirect()->route('checkout.show', $invoice->uuid);
    }

    // POST /pay/{uuid}/currency — customer picks a crypto for a fiat invoice.
    public function selectCurrency(string $uuid, Request $request, WalletService $wallets, RateService $rates): \Illuminate\Http\RedirectResponse
    {
        $invoice = PaymentInvoice::with('merchant')->where('uuid', $uuid)->firstOrFail();

        abort_if($invoice->isFinal() || ! $invoice->needsCurrencySelection(), 422);
        abort_if($invoice->expires_at && $invoice->expires_at->isPast(), 422);

        $code = (string) $request->input('currency');
        $currency = Currency::where('code', $code)->where('is_active', true)->firstOrFail();

        // Must be enabled for this merchant.
        abort_unless(
            $invoice->merchant->currencies()->where('currencies.id', $currency->id)->wherePivot('is_enabled', true)->exists(),
            422
        );

        $amount = $rates->convertFiatToCrypto($invoice->price_currency, $currency->code, (string) $invoice->price_amount);
        abort_if($amount === null, 422, 'Rate temporarily unavailable.');

        $wallet = $wallets->assignDepositWallet($currency, $invoice->merchant);

        $invoice->update([
            'currency_id' => $currency->id,
            'amount'      => $amount,
            'pay_address' => $wallet->address,
            'pay_memo'    => $wallet->memo,
        ]);
        $wallet->update(['invoice_id' => $invoice->id, 'is_used' => true]);

        AuditLog::record('invoice.currency_selected', $invoice, [], [], 'system');

        return redirect()->route('checkout.show', $invoice->uuid);
    }

    /** Build the per-crypto converted amounts for the fiat checkout selection. */
    private function currencyOptions(PaymentInvoice $invoice, RateService $rates): array
    {
        $currencies = $invoice->merchant->currencies()
            ->where('currencies.is_active', true)
            ->wherePivot('is_enabled', true)
            ->orderBy('currencies.code')
            ->get();

        $options = [];
        foreach ($currencies as $currency) {
            $amount = $rates->convertFiatToCrypto($invoice->price_currency, $currency->code, (string) $invoice->price_amount);
            if ($amount === null) {
                continue; // skip coins we can't price right now
            }
            $options[] = [
                'code'    => $currency->code,
                'name'    => $currency->name,
                'network' => $currency->network,
                'amount'  => rtrim(rtrim($amount, '0'), '.') ?: '0',
            ];
        }

        return $options;
    }

    /**
     * Lazy expiration: if the invoice window has passed, mark it expired and fire
     * the webhook immediately — so the customer doesn't wait for the minute cron.
     */
    private function expireIfOverdue(PaymentInvoice $invoice, WebhookService $webhooks): void
    {
        $overdue = in_array($invoice->status, ['pending', 'waiting_confirmations'], true)
            && $invoice->expires_at
            && $invoice->expires_at->isPast();

        if (! $overdue) {
            return;
        }

        $invoice->update(['status' => 'expired']);
        AuditLog::record('invoice.expired', $invoice, [], [], 'system');
        $webhooks->dispatch($invoice->refresh()->load('currency', 'merchant'), 'invoice.expired');
    }

    private function buildQrUri(PaymentInvoice $invoice): string
    {
        $address = $invoice->pay_address;
        $amount  = $invoice->payableAmount(); // invoice amount + transfer fee
        $network = $invoice->currency->network;

        $uri = match ($network) {
            'bitcoin'  => "bitcoin:{$address}?amount={$amount}",
            'litecoin' => "litecoin:{$address}?amount={$amount}",
            'dogecoin' => "dogecoin:{$address}?amount={$amount}",
            'ethereum', 'bsc' => "ethereum:{$address}",
            default    => $address,
        };

        return $uri;
    }
}
