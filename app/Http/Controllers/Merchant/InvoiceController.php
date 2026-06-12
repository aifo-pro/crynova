<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\Merchant;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    public function index(Request $request, Merchant $merchant)
    {
        $invoices = $merchant->invoices()
            ->with('currency')
            ->when($request->input('search'), fn ($q, $s) =>
                $q->where('order_id', 'like', "%{$s}%")
                  ->orWhere('uuid', 'like', "%{$s}%")
            )
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('currency'), fn ($q, $c) =>
                $q->whereHas('currency', fn ($cq) => $cq->where('code', $c))
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $currencies = Currency::where('is_active', true)->orderBy('code')->get();

        return view('merchant.invoices.index', compact('invoices', 'currencies'));
    }

    public function show(Request $request, Merchant $merchant, string $uuid)
    {
        $invoice = $merchant->invoices()
            ->with('currency', 'transactions', 'webhookLogs')
            ->where('uuid', $uuid)
            ->firstOrFail();

        return view('merchant.invoices.show', compact('merchant', 'invoice'));
    }

    public function create(Request $request, Merchant $merchant)
    {
        $currencies = Currency::where('is_active', true)->orderBy('code')->get();

        return view('merchant.invoices.create', compact('merchant', 'currencies'));
    }

    public function store(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'amount'      => ['required', 'numeric', 'gt:0'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'order_id'    => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'expires_in'  => ['nullable', 'integer', 'min:5', 'max:1440'],
            'metadata'    => ['nullable', 'string'],
        ]);

        // Parse optional JSON metadata — silently ignore invalid JSON
        $meta = null;
        if (!empty($validated['metadata'])) {
            $meta = json_decode($validated['metadata'], true);
        }

        $currency = Currency::findOrFail($validated['currency_id']);

        $payload = [
            'currency'    => $currency->code,
            'amount'      => $validated['amount'],
            'order_id'    => $validated['order_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'expires_in'  => $validated['expires_in'] ?? null,
            'metadata'    => $meta,
        ];

        $invoice = $this->invoiceService->create($merchant, $payload);

        AuditLog::record('invoice.created_via_ui', $invoice);

        return redirect()
            ->route('checkout.show', $invoice->uuid)
            ->with('success', __('flash.invoice_created_share'));
    }
}
