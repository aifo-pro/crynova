<?php

namespace App\Jobs;

use App\Models\WebhookLog;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetryWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 30;

    public function __construct(
        private readonly int $logId,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(WebhookService $webhook): void
    {
        $log = WebhookLog::with('merchant', 'invoice')->find($this->logId);

        if (! $log || $log->success) {
            return;
        }

        $maxAttempts = (int) config('crynova.webhook_max_attempts', 5);

        if ($log->attempt >= $maxAttempts) {
            return; // Give up after max retries
        }

        $webhook->retry($log);
    }
}
