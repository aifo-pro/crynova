<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds native ETH on the new EVM L2 networks (Arbitrum, Optimism, Base).
 * These need no token contract and reuse the existing ETH HD keys / Etherscan
 * V2 explorer. Token currencies (USDC/USDT/DAI/…) are added by the operator via
 * Admin → Currencies → "Add currency" with their verified contract addresses.
 */
return new class extends Migration
{
    public function up(): void
    {
        $natives = [
            ['code' => 'ETH_ARB',  'name' => 'Ethereum (Arbitrum)', 'network' => 'arbitrum'],
            ['code' => 'ETH_OPT',  'name' => 'Ethereum (Optimism)', 'network' => 'optimism'],
            ['code' => 'ETH_BASE', 'name' => 'Ethereum (Base)',     'network' => 'base'],
        ];

        foreach ($natives as $i => $c) {
            DB::table('currencies')->updateOrInsert(
                ['code' => $c['code']],
                [
                    'name'                   => $c['name'],
                    'network'                => $c['network'],
                    'contract_address'       => null,
                    'decimals'               => 18,
                    'confirmations_required' => 12,
                    'min_amount'             => '0.0005',
                    'estimated_fee'          => '0.0001',
                    'supports_memo'          => false,
                    'is_active'              => false, // operator enables after confirming the Etherscan key
                    'updated_at'             => now(),
                    'created_at'             => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('currencies')->whereIn('code', ['ETH_ARB', 'ETH_OPT', 'ETH_BASE'])->delete();
    }
};
