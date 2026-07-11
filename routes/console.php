<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Records the last-run timestamp of a scheduled command so the admin Health
// page can show whether cron is actually firing.
$track = fn (string $name) => function () use ($name) {
    Cache::put("schedule:last:{$name}", now()->toIso8601String(), now()->addDays(3));
};

// Poll blockchain for payment confirmations every minute
Schedule::command('crynova:poll-invoices')->everyMinute()->withoutOverlapping()->after($track('crynova:poll-invoices'));

// Expire overdue invoices every minute
Schedule::command('crynova:expire-invoices')->everyMinute()->withoutOverlapping()->after($track('crynova:expire-invoices'));

// Scan static deposit wallets and credit confirmed transfers
Schedule::command('crynova:scan-deposits')->everyMinute()->withoutOverlapping()->after($track('crynova:scan-deposits'));

// Retry failed webhooks every 5 minutes
Schedule::command('crynova:retry-webhooks')->everyFiveMinutes()->withoutOverlapping()->after($track('crynova:retry-webhooks'));

// Send daily Telegram summaries to merchants
Schedule::command('crynova:telegram-daily-reports')->dailyAt('09:00')->withoutOverlapping()->after($track('crynova:telegram-daily-reports'));

// Purge expired API idempotency keys daily
Schedule::call(function () {
    \App\Models\ApiIdempotencyKey::where('expires_at', '<', now())->delete();
})->daily();
