<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Poll blockchain for payment confirmations every minute
Schedule::command('crynova:poll-invoices')->everyMinute()->withoutOverlapping();

// Expire overdue invoices every minute
Schedule::command('crynova:expire-invoices')->everyMinute()->withoutOverlapping();

// Retry failed webhooks every 5 minutes
Schedule::command('crynova:retry-webhooks')->everyFiveMinutes()->withoutOverlapping();

// Send daily Telegram summaries to merchants
Schedule::command('crynova:telegram-daily-reports')->dailyAt('09:00')->withoutOverlapping();

// Purge expired API idempotency keys daily
Schedule::call(function () {
    \App\Models\ApiIdempotencyKey::where('expires_at', '<', now())->delete();
})->daily();
