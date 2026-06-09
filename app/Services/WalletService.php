<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Merchant;
use App\Models\Wallet;
use App\Services\Blockchain\BlockchainDriverFactory;
use App\Services\Blockchain\TestBlockchainDriver;

class WalletService
{
    public function __construct(
        private readonly HdWalletService $hdWallet,
        private readonly BlockchainDriverFactory $driverFactory,
    ) {}

    /** Returns a free hot-wallet address for the given currency. */
    public function assignDepositWallet(Currency $currency, ?Merchant $merchant = null): Wallet
    {
        if ($merchant?->test_mode) {
            return $this->createFromDerivation(
                $currency,
                app(TestBlockchainDriver::class)->deriveAddress($this->nextIndex($currency))
            );
        }

        $wallet = Wallet::where('currency_id', $currency->id)
            ->where('type', 'hot')
            ->where('is_used', false)
            ->whereNull('invoice_id')
            ->lockForUpdate()
            ->first();

        if ($wallet) {
            return $wallet;
        }

        return $this->deriveNextAddress($currency, $merchant);
    }

    /** Always mint a new pool address (used by crynova:generate-addresses). */
    public function preGenerateDepositWallet(Currency $currency): Wallet
    {
        return $this->deriveNextAddress($currency);
    }

    private function deriveNextAddress(Currency $currency, ?Merchant $merchant = null): Wallet
    {
        $index = $this->nextIndex($currency);

        if ($this->hdWallet->hasXpub($this->hdNetworkKey($currency->network))) {
            $result = $this->hdWallet->deriveForCurrency($currency, $index);

            return $this->createFromDerivation($currency, $result);
        }

        $driver = $this->driverFactory->forCurrency($currency, $merchant);
        $result = $driver->deriveAddress($index);

        return $this->createFromDerivation($currency, $result);
    }

    private function nextIndex(Currency $currency): int
    {
        return Wallet::where('currency_id', $currency->id)->where('type', 'hot')->count();
    }

    private function createFromDerivation(Currency $currency, array $result): Wallet
    {
        return Wallet::create([
            'currency_id' => $currency->id,
            'address'     => $result['address'],
            'memo'        => $result['memo'] ?? null,
            'hd_path'     => $result['path'] ?? null,
            'type'        => 'hot',
            'is_used'     => false,
        ]);
    }

    private function hdNetworkKey(string $network): string
    {
        return match ($network) {
            'bsc' => 'ethereum',
            default => $network,
        };
    }
}
