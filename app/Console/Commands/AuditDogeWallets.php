<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\Wallet;
use App\Services\HdWalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditDogeWallets extends Command
{
    protected $signature = 'crynova:audit-doge-wallets
                            {--csv : Output full comparison as CSV}
                            {--focus= : Comma-separated wallet ids for RPC/HD analysis (default: 48,187)}';

    protected $description = 'Compare all DOGE wallet addresses with derivation from current hd_xpub_doge';

    public function handle(HdWalletService $hd): int
    {
        $currency = Currency::where('code', 'DOGE')->first();

        if (! $currency) {
            $this->error('DOGE currency not found.');

            return self::FAILURE;
        }

        if (! $hd->hasXpub('dogecoin')) {
            $this->error('hd_xpub_doge is not configured — cannot derive.');

            return self::FAILURE;
        }

        $meta = $hd->inspectDogecoinAccountKey();
        $wallets = Wallet::where('currency_id', $currency->id)
            ->orderBy('id')
            ->get(['id', 'address', 'hd_path', 'invoice_id', 'is_used', 'created_at']);

        $this->info('Current hd_xpub_doge');
        $this->table(['Field', 'Value'], [
            ['source', $meta['source']],
            ['raw SHA256', $meta['raw_sha256']],
            ['normalized SHA256', $meta['normalized_sha256']],
            ['key fingerprint', $meta['key_fingerprint']],
            ['pubkey HASH160', $meta['pubkey_hash160']],
            ['BIP32 depth', $meta['depth'].' (expected '.$meta['expected_account_depth'].')'],
        ]);

        $this->newLine();
        $this->info('DOGE wallets: '.$wallets->count());

        $rows = [];
        $matches = 0;
        $mismatches = 0;
        $firstMismatchId = null;
        $unparseable = 0;

        foreach ($wallets as $wallet) {
            $index = $this->parseIndex($wallet->hd_path);
            $derived = '—';
            $match = '—';

            if ($index === null) {
                $unparseable++;
                $match = 'no_index';
            } else {
                try {
                    $result = $hd->deriveDogecoinFromStoredXpub($index, normalize: true);
                    $derived = $result['address'];
                    $match = strcasecmp($derived, $wallet->address) === 0 ? 'YES' : 'NO';
                } catch (\Throwable $e) {
                    $derived = 'ERROR: '.$e->getMessage();
                    $match = 'ERROR';
                }

                if ($match === 'YES') {
                    $matches++;
                } elseif ($match === 'NO') {
                    $mismatches++;
                    $firstMismatchId ??= $wallet->id;
                }
            }

            $rows[] = [
                'wallet_id'                   => $wallet->id,
                'stored_address'              => $wallet->address,
                'hd_path'                     => $wallet->hd_path ?? '—',
                'index'                       => $index ?? '—',
                'derived_from_current_xpub'   => $derived,
                'match'                       => $match,
                'invoice_id'                  => $wallet->invoice_id ?? '—',
                'created_at'                  => optional($wallet->created_at)->toDateTimeString() ?? '—',
            ];
        }

        if ((bool) $this->option('csv')) {
            $this->outputCsv($rows);
        } else {
            $this->table(
                ['wallet_id', 'stored_address', 'derived_from_current_xpub', 'match', 'hd_path', 'created_at'],
                collect($rows)->map(fn ($r) => [
                    $r['wallet_id'],
                    $r['stored_address'],
                    $r['derived_from_current_xpub'],
                    $r['match'],
                    $r['hd_path'],
                    $r['created_at'],
                ])->all()
            );
        }

        $this->newLine();
        $this->info('Summary');
        $this->table(['Metric', 'Value'], [
            ['Total wallets', $wallets->count()],
            ['Match current xpub', $matches],
            ['Mismatch', $mismatches],
            ['Unparseable hd_path', $unparseable],
            ['First mismatch wallet_id', $firstMismatchId ?? 'none — all match'],
        ]);

        if ($firstMismatchId !== null) {
            $first = collect($rows)->firstWhere('wallet_id', $firstMismatchId);
            $this->warn("Divergence starts at wallet_id {$firstMismatchId} (created {$first['created_at']}, index {$first['index']}).");
            $this->line('Wallets with lower id that match → current xpub was valid then; later xpub changed OR RPC/other source used.');
        }

        $this->printSettingsHistory($firstMismatchId ? (int) $firstMismatchId : null, $wallets);
        $this->analyzeFocusWallets($hd, $rows);

        return $mismatches > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function parseIndex(?string $path): ?int
    {
        if ($path && preg_match("/0'\\/0\\/(\\d+)\$/", $path, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /** @param list<array<string, mixed>> $rows */
    private function outputCsv(array $rows): void
    {
        $out = fopen('php://output', 'w');
        fputcsv($out, array_keys($rows[0] ?? []));
        foreach ($rows as $row) {
            fputcsv($out, array_values($row));
        }
        fclose($out);
    }

    private function printSettingsHistory(?int $firstMismatchId, $wallets): void
    {
        $this->newLine();
        $this->info('hd_xpub_doge change history');

        $logs = AuditLog::where('action', 'settings.updated')
            ->orderBy('id')
            ->get(['id', 'user_id', 'actor_ip', 'created_at']);

        if ($logs->isEmpty()) {
            $this->warn('No audit_logs with action=settings.updated (admin saves settings without per-key detail).');
        } else {
            $this->table(
                ['audit_id', 'user_id', 'ip', 'created_at'],
                $logs->map(fn ($l) => [$l->id, $l->user_id ?? '—', $l->actor_ip ?? '—', $l->created_at])->all()
            );
        }

        $settingRow = DB::table('settings')->where('key', 'hd_xpub_doge')->first(['updated_at', 'created_at']);
        if ($settingRow) {
            $this->line('settings.hd_xpub_doge updated_at: '.($settingRow->updated_at ?? '—'));
            $this->line('settings.hd_xpub_doge created_at: '.($settingRow->created_at ?? '—'));
        }

        if ($firstMismatchId !== null) {
            $mismatchWallet = $wallets->firstWhere('id', $firstMismatchId);
            $lastMatch = Wallet::where('currency_id', $mismatchWallet->currency_id)
                ->where('id', '<', $firstMismatchId)
                ->orderByDesc('id')
                ->first();

            if ($lastMatch) {
                $this->line("Last matching wallet before divergence: #{$lastMatch->id} at {$lastMatch->created_at}");
            }

            $relevantLogs = $logs->filter(function ($log) use ($mismatchWallet, $lastMatch) {
                if (! $mismatchWallet?->created_at) {
                    return false;
                }
                $after = $lastMatch?->created_at ?? $mismatchWallet->created_at->copy()->subYear();
                $before = $mismatchWallet->created_at;

                return $log->created_at >= $after && $log->created_at <= $before;
            });

            if ($relevantLogs->isNotEmpty()) {
                $this->warn('settings.updated logged between last match and first mismatch — xpub may have been changed in admin.');
            } else {
                $this->line('No settings.updated between last matching wallet and first mismatch (or no prior match).');
            }
        }

        $this->line('Note: settings.updated does not log which keys changed — only that admin saved settings form.');
    }

    /** @param list<array<string, mixed>> $rows */
    private function analyzeFocusWallets(HdWalletService $hd, array $rows): void
    {
        $focus = collect(explode(',', (string) $this->option('focus')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($focus->isEmpty()) {
            $focus = collect([48, 187]);
        }

        $this->newLine();
        $this->info('Focus wallets — HD vs RPC inference');

        foreach ($focus as $walletId) {
            $row = collect($rows)->firstWhere('wallet_id', $walletId);

            if (! $row) {
                $this->warn("Wallet #{$walletId}: not found among DOGE wallets.");

                continue;
            }

            $inference = match ($row['match']) {
                'YES' => 'HD from current xpub (matches at hd_path index)',
                'NO'  => 'NOT from current xpub — RPC fallback OR different xpub at creation OR reused pool address from old xpub',
                default => 'Cannot determine (missing/invalid hd_path or derive error)',
            };

            $this->table(["Wallet #{$walletId}", 'Value'], [
                ['stored_address', $row['stored_address']],
                ['hd_path', $row['hd_path']],
                ['index', $row['index']],
                ['derived (current xpub)', $row['derived_from_current_xpub']],
                ['match', $row['match']],
                ['created_at', $row['created_at']],
                ['invoice_id', $row['invoice_id']],
                ['inference', $inference],
            ]);
        }

        $xpubConfiguredNow = $hd->hasXpub('dogecoin');
        $this->line('RPC fallback is used when hd_xpub_doge is empty at creation time (DogecoinDriver::deriveAddress → getnewaddress).');
        $this->line('hd_xpub_doge configured now: '.($xpubConfiguredNow ? 'yes' : 'no'));
        $this->line('Historical RPC usage cannot be proven from DB alone — mismatch + no settings.updated near creation suggests RPC or manual DB edit.');
    }
}
