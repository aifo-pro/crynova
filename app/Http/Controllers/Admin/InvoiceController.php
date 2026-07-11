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
        $invoices = $this->filtered($request)
            ->with('merchant', 'currency')
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
     * Shared filter chain for the index list and the CSV export so both stay in sync.
     */
    private function filtered(Request $request)
    {
        return PaymentInvoice::query()
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
            );
    }

    /**
     * Stream the current (filtered) invoice list as a CSV download.
     */
    public function export(Request $request)
    {
        $filename = 'invoices_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = ['UUID', 'Order ID', 'Merchant', 'Currency', 'Amount', 'Received', 'Status', 'Created'];

        $callback = function () use ($request, $columns) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, $columns);

            $this->filtered($request)
                ->with('merchant', 'currency')
                ->latest()
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $inv) {
                        fputcsv($out, [
                            $inv->uuid,
                            $inv->order_id,
                            $inv->merchant?->name,
                            optional($inv->currency)->code ?? $inv->price_currency,
                            $inv->amount ?? $inv->price_amount,
                            $inv->amount_received,
                            $inv->status,
                            optional($inv->created_at)->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($out);
        };

        return response()->streamDownload($callback, $filename, $headers);
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
