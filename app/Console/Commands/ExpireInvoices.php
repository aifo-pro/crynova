<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\PaymentInvoice;
use App\Services\WebhookService;
use Illuminate\Console\Command;

class ExpireInvoices extends Command
{
    protected $signature   = 'crynova:expire-invoices';
    protected $description = 'Mark overdue pending invoices as expired and send webhooks';

    public function handle(WebhookService $webhook): int
    {
        $expired = PaymentInvoice::whereIn('status', ['pending', 'waiting_confirmations'])
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $invoice) {
            $invoice->update(['status' => 'expired']);
            AuditLog::record('invoice.expired', $invoice, [], [], 'system');
            $webhook->dispatch($invoice, 'invoice.expired');
        }

        $this->info("Expired {$expired->count()} invoices.");

        return self::SUCCESS;
    }
}
