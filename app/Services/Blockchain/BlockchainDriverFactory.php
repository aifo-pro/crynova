<?php

namespace App\Services\Blockchain;

use App\Models\Currency;
use App\Models\Merchant;
use App\Services\BlockchainDriverInterface;

class BlockchainDriverFactory
{
    public function forCurrency(Currency $currency, ?Merchant $merchant = null): BlockchainDriverInterface
    {
        if ($merchant?->test_mode) {
            return app(TestBlockchainDriver::class);
        }

        return match ($currency->network) {
            'bitcoin'  => app(BitcoinDriver::class),
            'ethereum', 'bsc' => app(EthereumDriver::class),
            'tron'     => app(TronDriver::class),
            'litecoin' => app(LitecoinDriver::class),
            'dogecoin' => app(DogecoinDriver::class),
            default    => throw new \RuntimeException("No driver for network: {$currency->network}"),
        };
    }
}
