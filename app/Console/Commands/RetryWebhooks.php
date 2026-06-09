<?php

namespace App\Console\Commands;

use App\Jobs\RetryWebhookJob;
use App\Models\WebhookLog;
use Illuminate\Console\Command;

class RetryWebhooks extends Command
{
    protected $signature   = 'crynova:retry-webhooks';
    protected $description = 'Re-queue failed webhooks that are due for retry';

    public function handle(): int
    {
        $maxAttempts = (int) config('crynova.webhook_max_attempts', 5);

        $logs = WebhookLog::where('success', false)
            ->where('attempt', '<', $maxAttempts)
            ->where('next_retry_at', '<=', now())
            ->get();

        foreach ($logs as $log) {
            RetryWebhookJob::dispatch($log->id);
        }

        $this->info("Queued {$logs->count()} webhook retries.");

        return self::SUCCESS;
    }
}
