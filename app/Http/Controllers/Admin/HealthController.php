<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\PaymentInvoice;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function index()
    {
        // Database connectivity.
        $dbOk = true;
        try {
            DB::connection()->getPdo();
            DB::select('select 1');
        } catch (\Throwable $e) {
            $dbOk = false;
        }

        $system = [
            'db'          => $dbOk,
            'php'         => PHP_VERSION,
            'laravel'     => app()->version(),
            'environment' => app()->environment(),
            'debug'       => (bool) config('app.debug'),
            'cache'       => config('cache.default'),
            'queue'       => config('queue.default'),
            'session'     => config('session.driver'),
        ];

        $pendingRetries = WebhookLog::where('success', false)->whereNotNull('next_retry_at')->count();
        $deadWebhooks   = WebhookLog::where('success', false)->whereNull('next_retry_at')->count();
        $oldestRetry    = WebhookLog::where('success', false)->whereNotNull('next_retry_at')->min('next_retry_at');

        $webhooks = [
            'pending_retries' => $pendingRetries,
            'dead'            => $deadWebhooks,
            'oldest_retry'    => $oldestRetry,
        ];

        $invoices = [
            'pending' => PaymentInvoice::whereIn('status', ['pending', 'waiting_confirmations'])->count(),
            'stuck'   => PaymentInvoice::where('status', 'waiting_confirmations')
                ->where('updated_at', '<', now()->subMinutes(30))->count(),
            'last_paid' => PaymentInvoice::whereIn('status', ['paid', 'overpaid'])->max('paid_at'),
        ];

        $currencies = Currency::where('is_active', true)->orderBy('code')->get(['code', 'network']);

        // Scheduled jobs configured in routes/console.php (informational).
        $schedule = [
            ['crynova:poll-invoices', 'Кожну хвилину'],
            ['crynova:expire-invoices', 'Кожну хвилину'],
            ['crynova:scan-deposits', 'Кожну хвилину'],
            ['crynova:retry-webhooks', 'Кожні 5 хвилин'],
            ['crynova:telegram-daily-reports', 'Щодня о 09:00'],
        ];

        return view('admin.health', compact('system', 'webhooks', 'invoices', 'currencies', 'schedule'));
    }
}
