<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\PaymentInvoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = PaymentInvoice::with('merchant', 'currency')
            ->when($request->input('search'), function ($q, $s) {
                $q->where(function ($query) use ($s) {
                    $query->where('uuid', 'like', "%{$s}%")
                        ->orWhere('order_id', 'like', "%{$s}%")
                        ->orWhere('pay_address', 'like', "%{$s}%")
                        ->orWhereHas('merchant', fn ($merchant) => $merchant
                            ->where('name', 'like', "%{$s}%")
                            ->orWhere('domain', 'like', "%{$s}%"));
                });
            })
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('currency'), fn ($q, $c) =>
                $q->whereHas('currency', fn ($cq) => $cq->where('code', $c))
            )
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $currencies = Currency::orderBy('code')->get();
        $statuses   = PaymentInvoice::distinct()->pluck('status')->sort();
        $stats = [
            'total' => PaymentInvoice::count(),
            'paid' => PaymentInvoice::where('status', 'paid')->count(),
            'pending' => PaymentInvoice::whereIn('status', ['pending', 'waiting_confirmations'])->count(),
            'volume' => PaymentInvoice::where('status', 'paid')->sum('amount_received'),
        ];

        return view('admin.invoices.index', compact('invoices', 'currencies', 'statuses', 'stats'));
    }

    public function show(PaymentInvoice $invoice)
    {
        $invoice->load('merchant', 'currency', 'transactions', 'webhookLogs');

        return view('admin.invoices.show', compact('invoice'));
    }
}
