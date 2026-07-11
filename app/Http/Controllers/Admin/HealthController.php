<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\PaymentInvoice;
use App\Models\WebhookLog;
use App\Services\Blockchain\BlockchainDriverFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function index(BlockchainDriverFactory $driverFactory)
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

        // Oldest webhook deliveries still awaiting retry.
        $webhookQueue = WebhookLog::with('invoice')
            ->where('success', false)
            ->whereNotNull('next_retry_at')
            ->orderBy('next_retry_at')
            ->limit(10)
            ->get();

        // Blockchain node reachability — one representative currency per network.
        // Cached to avoid hitting every RPC on each page load.
        $nodes = Cache::remember('health:nodes', 120, function () use ($driverFactory) {
            // EVM chains share one driver + RPC endpoint, so probe them once.
            $evm = ['ethereum', 'bsc', 'arbitrum', 'optimism', 'base'];
            $result = [];
            $seenGroups = [];

            foreach (Currency::where('is_active', true)->get()->unique('network') as $currency) {
                if (! $currency->network) {
                    continue;
                }

                $isEvm = in_array($currency->network, $evm, true);
                $group = $isEvm ? 'evm' : $currency->network;
                if (isset($seenGroups[$group])) {
                    continue;
                }
                $seenGroups[$group] = true;

                $entry = [
                    'network' => $isEvm ? 'EVM (ETH / BSC / L2)' : $currency->network,
                    'ok'      => false,
                    'height'  => null,
                    'error'   => null,
                ];
                try {
                    $entry['height'] = $driverFactory->forCurrency($currency)->getBlockHeight();
                    $entry['ok'] = $entry['height'] > 0;
                } catch (\Throwable $e) {
                    $entry['error'] = $e->getMessage();
                }
                $result[] = $entry;
            }

            return $result;
        });

        // Scheduled jobs + last observed run (recorded via cache in routes/console.php).
        $schedule = collect([
            ['crynova:poll-invoices', 'Кожну хвилину'],
            ['crynova:expire-invoices', 'Кожну хвилину'],
            ['crynova:scan-deposits', 'Кожну хвилину'],
            ['crynova:retry-webhooks', 'Кожні 5 хвилин'],
            ['crynova:telegram-daily-reports', 'Щодня о 09:00'],
        ])->map(fn ($row) => [
            'cmd'      => $row[0],
            'freq'     => $row[1],
            'last_run' => Cache::get("schedule:last:{$row[0]}"),
        ])->all();

        return view('admin.health', compact('system', 'webhooks', 'webhookQueue', 'invoices', 'currencies', 'nodes', 'schedule'));
    }
}
