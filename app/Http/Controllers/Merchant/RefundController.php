<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Merchant;
use App\Models\Refund;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index(Request $request, Merchant $merchant)
    {
        $refunds = $merchant->refunds()
            ->with('invoice.currency', 'currency')
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('merchant.refunds.index', compact('refunds'));
    }

    public function store(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'invoice_id' => ['required', 'integer', 'exists:payment_invoices,id'],
            'amount'     => ['required', 'numeric', 'gt:0'],
            'to_address' => ['required', 'string', 'max:255'],
            'memo'       => ['nullable', 'string', 'max:255'],
            'reason'     => ['nullable', 'string', 'max:500'],
        ]);

        // Verify invoice belongs to this merchant
        $invoice = $merchant->invoices()
            ->whereIn('status', ['paid', 'overpaid'])
            ->findOrFail($validated['invoice_id']);

        // Determine type
        $type = bccomp((string) $validated['amount'], (string) $invoice->amount_received, 8) === 0
            ? 'full'
            : 'partial';

        $refund = Refund::create([
            'merchant_id' => $merchant->id,
            'invoice_id'  => $invoice->id,
            'currency_id' => $invoice->currency_id,
            'amount'      => $validated['amount'],
            'to_address'  => $validated['to_address'],
            'memo'        => $validated['memo'] ?? null,
            'reason'      => $validated['reason'] ?? null,
            'type'        => $type,
            'status'      => 'pending',
        ]);

        AuditLog::record('refund.requested', $refund);

        return back()->with('success', 'Refund request submitted. Admin will review and process it.');
    }
}
