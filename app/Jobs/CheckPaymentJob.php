<?php

namespace App\Jobs;

use App\Models\PaymentInvoice;
use App\Services\PaymentCheckerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        private readonly int $invoiceId,
    ) {
        $this->onQueue('blockchain');
    }

    public function handle(PaymentCheckerService $checker): void
    {
        $invoice = PaymentInvoice::with('currency', 'merchant', 'transactions')->find($this->invoiceId);

        if (! $invoice) {
            return;
        }

        // Webhooks for status transitions are dispatched inside the checker
        // (post-commit) so they fire on every detection path, not just here.
        $checker->check($invoice);
    }
}
