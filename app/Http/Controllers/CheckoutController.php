<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\PaymentInvoice;
use App\Models\PaymentLink;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    // GET /pay/{uuid}
    public function show(string $uuid): View|\Illuminate\Http\RedirectResponse
    {
        $invoice = PaymentInvoice::with('currency', 'merchant', 'transactions')
            ->where('uuid', $uuid)
            ->firstOrFail();

        if ($invoice->isFinal() && $invoice->status !== 'waiting_confirmations') {
            return view('checkout.final', compact('invoice'));
        }

        // QR code data URI (address or BIP-21 URI)
        $qrData = $this->buildQrUri($invoice);

        return view('checkout.show', compact('invoice', 'qrData'));
    }

    // GET /pay/{uuid}/status  — polled by JS every 5s
    public function status(string $uuid): \Illuminate\Http\JsonResponse
    {
        $invoice = PaymentInvoice::with('currency', 'transactions')
            ->where('uuid', $uuid)
            ->select(['id', 'uuid', 'currency_id', 'status', 'amount_received', 'expires_at', 'paid_at'])
            ->firstOrFail();

        return response()->json([
            'status'          => $invoice->status,
            'amount_received' => (string) $invoice->amount_received,
            'confirmations'   => (int) $invoice->transactions->max('confirmations'),
            'confirmations_required' => (int) $invoice->currency->confirmations_required,
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
            return back()->with('error', 'Тимчасово недоступне створення платежу для обраної валюти. Спробуйте іншу мережу.');
        }

        return redirect()->route('checkout.show', $invoice->uuid);
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
