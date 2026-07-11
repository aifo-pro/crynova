<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\PaymentInvoice;
use App\Models\WebhookLog;
use App\Services\PaymentCheckerService;
use App\Services\WebhookService;
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

    /**
     * Force an on-demand blockchain re-poll for a single invoice.
     * Safe: PaymentCheckerService::check() is idempotent and no-ops on final invoices.
     */
    public function recheck(PaymentInvoice $invoice, PaymentCheckerService $checker)
    {
        if ($invoice->isFinal()) {
            return back()->with('error', 'Рахунок уже завершено — перевірка не потрібна.');
        }

        $checker->check($invoice);
        AuditLog::record('invoice.rechecked', $invoice->fresh(), [], [], 'admin');

        return back()->with('success', 'Блокчейн перевірено. Статус оновлено.');
    }

    /**
     * Manually cancel an unpaid invoice. Never touches a paid/settled invoice.
     */
    public function cancel(PaymentInvoice $invoice)
    {
        if (! in_array($invoice->status, ['pending', 'waiting_confirmations'], true)) {
            return back()->with('error', 'Скасувати можна лише рахунок в очікуванні оплати.');
        }

        $old = ['status' => $invoice->status];
        $invoice->update(['status' => 'cancelled']);
        AuditLog::record('invoice.cancelled', $invoice, $old, ['status' => 'cancelled'], 'admin');

        return back()->with('success', 'Рахунок скасовано.');
    }

    /**
     * Re-deliver a specific webhook log for this invoice.
     */
    public function resendWebhook(PaymentInvoice $invoice, WebhookLog $log, WebhookService $webhooks)
    {
        abort_unless($log->invoice_id === $invoice->id, 404);

        $webhooks->retry($log);
        AuditLog::record('invoice.webhook_resent', $invoice, [], ['event' => $log->event], 'admin');

        return back()->with('success', 'Webhook повторно надіслано.');
    }
}
