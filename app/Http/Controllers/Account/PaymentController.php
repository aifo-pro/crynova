<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\PaymentInvoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $merchantIds = $request->user()->accessibleMerchantIds();

        $base = PaymentInvoice::whereIn('merchant_id', $merchantIds);

        $createdSum = (clone $base)->sum('amount');
        $paidSum = (clone $base)->where('status', 'paid')->sum('amount_received');
        $partialSum = (clone $base)->where('status', 'underpaid')->sum('amount_received');
        $createdCnt = (clone $base)->count();
        $paidCnt = (clone $base)->where('status', 'paid')->count();
        $conversion = $createdCnt > 0 ? round($paidCnt / $createdCnt * 100, 2) : 0.0;

        $stats = compact('createdSum', 'paidSum', 'partialSum', 'conversion');

        $invoices = PaymentInvoice::whereIn('merchant_id', $merchantIds)
            ->with('currency', 'merchant')
            ->when($request->input('search'), fn ($q, $s) =>
                $q->where('order_id', 'like', "%{$s}%")->orWhere('uuid', 'like', "%{$s}%"))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('project'), fn ($q, $p) => $q->where('merchant_id', $p))
            ->when($request->input('currency'), fn ($q, $c) => $q->where('currency_id', $c))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $projects = $request->user()->merchants()->get();
        $currencies = Currency::where('is_active', true)->orderBy('code')->get();

        return view('account.payments', compact('invoices', 'stats', 'projects', 'currencies'));
    }

    public function create(Request $request)
    {
        $projects = $request->user()->merchants()->where('status', 'active')->get();
        $currencies = Currency::where('is_active', true)->orderBy('code')->get();
        $fiatCurrencies = (array) config('crynova.fiat_currencies', []);

        return view('account.payment-create', compact('projects', 'currencies', 'fiatCurrencies'));
    }

    public function store(Request $request, InvoiceService $invoiceService)
    {
        $fiatList = (array) config('crynova.fiat_currencies', []);

        $validated = $request->validate([
            'merchant_id'   => ['required', 'integer'],
            'fiat_currency' => ['nullable', 'string', 'in:' . implode(',', $fiatList)],
            'currency_id'   => ['nullable', 'integer', 'exists:currencies,id', 'required_without:fiat_currency'],
            'amount'        => ['required', 'numeric', 'gt:0'],
        ]);

        $merchant = $request->user()->merchants()
            ->where('id', $validated['merchant_id'])
            ->firstOrFail();

        abort_unless($merchant->featuresUnlocked(), 403, __('account.payments.project_inactive'));

        // Fiat-priced invoice (customer picks crypto at checkout) takes precedence.
        $currencyCode = ! empty($validated['fiat_currency'])
            ? $validated['fiat_currency']
            : Currency::findOrFail($validated['currency_id'])->code;

        try {
            $invoice = $invoiceService->create($merchant, [
                'currency' => $currencyCode,
                'amount'   => $validated['amount'],
                'metadata' => ['source' => 'manual'],
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', __('account.payments.create_failed'));
        }

        return redirect()->route('checkout.show', $invoice->uuid)
            ->with('success', __('account.payments.invoice_created'));
    }
}
