<?php

namespace App\Jobs;

use App\Models\PaymentInvoice;
use App\Services\PaymentCheckerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckPaymentJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;
    public int $uniqueFor = 120;

    public function __construct(
        private readonly int $invoiceId,
    ) {
        $this->onQueue('blockchain');
    }

    public function uniqueId(): string
    {
        return 'check-payment:' . $this->invoiceId;
    }

    public function handle(PaymentCheckerService $checker): void
    {
        $invoice = PaymentInvoice::with('currency', 'merchant', 'transactions')->find($this->invoiceId);

        if (! $invoice) {
            return;
        }

        $checker->check($invoice);
    }
}
