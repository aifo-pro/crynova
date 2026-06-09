<?php

namespace App\Console\Commands;

use App\Models\PaymentInvoice;
use App\Services\PaymentCheckerService;
use App\Services\WebhookService;
use Illuminate\Console\Command;

class SimulateTestPayment extends Command
{
    protected $signature = 'crynova:simulate-payment {invoice : Invoice UUID} {--amount= : Override amount received}';

    protected $description = 'Simulate an incoming payment for a test_mode merchant invoice';

    public function handle(PaymentCheckerService $checker, WebhookService $webhooks): int
    {
        $invoice = PaymentInvoice::with('currency', 'merchant', 'transactions')
            ->where('uuid', $this->argument('invoice'))
            ->firstOrFail();

        if (! $invoice->merchant->test_mode) {
            $this->error('Merchant test_mode is off. Enable sandbox in merchant control panel first.');

            return self::FAILURE;
        }

        $oldStatus = $invoice->status;

        $checker->simulateTestPayment($invoice, $this->option('amount'));

        $invoice->refresh();

        if ($invoice->status !== $oldStatus) {
            $webhooks->dispatch($invoice, "invoice.{$invoice->status}");
        }

        $this->info("Invoice {$invoice->uuid} → {$invoice->status} (received: {$invoice->amount_received})");

        return self::SUCCESS;
    }
}
