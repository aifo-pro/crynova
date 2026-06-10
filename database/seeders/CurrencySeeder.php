<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            [
                'code'                   => 'BTC',
                'name'                   => 'Bitcoin',
                'network'                => 'bitcoin',
                'decimals'               => 8,
                'confirmations_required' => 3,
                'min_amount'             => '0.00001000',
            ],
            [
                'code'                   => 'ETH',
                'name'                   => 'Ethereum',
                'network'                => 'ethereum',
                'decimals'               => 18,
                'confirmations_required' => 12,
                'min_amount'             => '0.001000000000000000',
            ],
            [
                'code'                   => 'USDT_ERC20',
                'name'                   => 'Tether USD (ERC-20)',
                'network'                => 'ethereum',
                'contract_address'       => '0xdAC17F958D2ee523a2206206994597C13D831ec7',
                'decimals'               => 6,
                'confirmations_required' => 12,
                'min_amount'             => '1.000000',
            ],
            [
                'code'                   => 'USDT_TRC20',
                'name'                   => 'Tether USD (TRC-20)',
                'network'                => 'tron',
                'contract_address'       => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
                'decimals'               => 6,
                'confirmations_required' => 20,
                'min_amount'             => '1.000000',
            ],
            [
                'code'                   => 'USDT_BEP20',
                'name'                   => 'Tether USD (BEP-20)',
                'network'                => 'bsc',
                'contract_address'       => '0x55d398326f99059fF775485246999027B3197955',
                'decimals'               => 18,
                'confirmations_required' => 15,
                'min_amount'             => '1.000000000000000000',
            ],
            [
                'code'                   => 'TRX',
                'name'                   => 'TRON',
                'network'                => 'tron',
                'decimals'               => 6,
                'confirmations_required' => 20,
                'min_amount'             => '1.000000',
            ],
            [
                'code'                   => 'LTC',
                'name'                   => 'Litecoin',
                'network'                => 'litecoin',
                'decimals'               => 8,
                'confirmations_required' => 6,
                'min_amount'             => '0.00100000',
            ],
            [
                'code'                   => 'DOGE',
                'name'                   => 'Dogecoin',
                'network'                => 'dogecoin',
                'decimals'               => 8,
                'confirmations_required' => 6,
                'min_amount'             => '1.00000000',
            ],
        ];

        foreach ($currencies as $data) {
            // firstOrCreate: add new default currencies, but never reset an
            // existing currency's admin-configured fee / active flag.
            Currency::firstOrCreate(['code' => $data['code']], $data);
        }
    }
}
