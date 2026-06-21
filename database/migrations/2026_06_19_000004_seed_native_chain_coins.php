<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds native coins for the new chains (no token contract → zero address risk):
 * BNB (BSC), SOL (Solana), TON. Also activates the previously-seeded native ETH
 * on the L2s. Token currencies (USDC/USDT/DAI/…) are added via Admin → Currencies
 * → "Add currency" with their verified contract addresses.
 */
return new class extends Migration
{
    public function up(): void
    {
        $coins = [
            // code, name, network, decimals, confirmations, memo, active
            ['BNB', 'BNB (BSC)',  'bsc',      18, 15, false, true],
            ['SOL', 'Solana',     'solana',    9,  1, true,  false], // enable after setting Solana deposit address
            ['TON', 'Toncoin',    'ton',       9,  1, true,  false], // enable after setting TON deposit address
            // Activate native ETH on the L2s seeded earlier.
            ['ETH_ARB',  'Ethereum (Arbitrum)', 'arbitrum', 18, 12, false, true],
            ['ETH_OPT',  'Ethereum (Optimism)', 'optimism', 18, 12, false, true],
            ['ETH_BASE', 'Ethereum (Base)',     'base',     18, 12, false, true],
        ];

        foreach ($coins as [$code, $name, $network, $decimals, $conf, $memo, $active]) {
            $exists = DB::table('currencies')->where('code', $code)->exists();
            DB::table('currencies')->updateOrInsert(
                ['code' => $code],
                array_filter([
                    'name'                   => $name,
                    'network'                => $network,
                    'contract_address'       => null,
                    'decimals'               => $decimals,
                    'confirmations_required' => $conf,
                    'supports_memo'          => $memo,
                    'min_amount'             => '0',
                    'estimated_fee'          => '0',
                    'is_active'              => $active,
                    'updated_at'             => now(),
                    'created_at'             => $exists ? null : now(),
                ], fn ($v) => $v !== null)
            );
        }
    }

    public function down(): void
    {
        DB::table('currencies')->whereIn('code', ['BNB', 'SOL', 'TON'])->delete();
        DB::table('currencies')->whereIn('code', ['ETH_ARB', 'ETH_OPT', 'ETH_BASE'])->update(['is_active' => false]);
    }
};
