<?php

namespace App\Console\Commands;

use App\Jobs\CheckPaymentJob;
use App\Models\PaymentInvoice;
use Illuminate\Console\Command;

class PollPendingInvoices extends Command
{
    protected $signature   = 'crynova:poll-invoices';
    protected $description = 'Dispatch payment-check jobs for all pending/confirming invoices';

    public function handle(): int
    {
        $invoices = PaymentInvoice::whereIn('status', ['pending', 'waiting_confirmations'])
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->get();

        foreach ($invoices as $invoice) {
            CheckPaymentJob::dispatch($invoice->id);
        }

        $this->info("Dispatched {$invoices->count()} payment check jobs.");

        return self::SUCCESS;
    }
}
