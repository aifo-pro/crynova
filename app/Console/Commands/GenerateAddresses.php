<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\Wallet;
use App\Services\HdWalletService;
use App\Services\WalletService;
use Illuminate\Console\Command;

class GenerateAddresses extends Command
{
    protected $signature = 'crynova:generate-addresses
                            {--currency= : Currency code (BTC, ETH, …). All active currencies if omitted}
                            {--count=20 : Number of addresses to pre-generate per currency}
                            {--force : Create even if free wallets already exist}';

    protected $description = 'Pre-fill the hot-wallet address pool from HD xpubs or blockchain nodes';

    public function handle(WalletService $wallets, HdWalletService $hd): int
    {
        $codes = $this->option('currency')
            ? collect(explode(',', (string) $this->option('currency')))->map(fn ($c) => trim($c))->filter()
            : Currency::where('is_active', true)->pluck('code');

        $count = max(1, (int) $this->option('count'));
        $force = (bool) $this->option('force');

        foreach ($codes as $code) {
            $currency = Currency::where('code', $code)->where('is_active', true)->first();

            if (! $currency) {
                $this->warn("Currency {$code} not found or inactive — skipped.");

                continue;
            }

            $free = Wallet::where('currency_id', $currency->id)
                ->where('type', 'hot')
                ->where('is_used', false)
                ->whereNull('invoice_id')
                ->count();

            if ($free > 0 && ! $force) {
                $this->line("{$code}: {$free} free wallet(s) already in pool — use --force to add more.");

                continue;
            }

            $networkKey = match ($currency->network) {
                'bsc' => 'ethereum',
                default => $currency->network,
            };

            if (! $hd->hasXpub($networkKey)) {
                $this->warn("{$code}: no HD xpub for {$networkKey}. Configure hd_xpub_* in admin settings or HD_XPUB_* in .env, or ensure RPC node is online.");

                continue;
            }

            $created = 0;

            for ($i = 0; $i < $count; $i++) {
                try {
                    $wallets->preGenerateDepositWallet($currency);
                    $created++;
                } catch (\Throwable $e) {
                    $this->error("{$code}: failed at index {$i}: " . $e->getMessage());
                    break;
                }
            }

            $this->info("{$code}: generated {$created} address(es).");
        }

        return self::SUCCESS;
    }
}
