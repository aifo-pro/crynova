<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Merchant;
use App\Models\Setting;
use App\Models\Wallet;
use App\Services\Blockchain\BlockchainDriverFactory;
use App\Services\Blockchain\TestBlockchainDriver;

class WalletService
{
    /** ed25519 chains that share one deposit address and distinguish invoices by memo. */
    private const MEMO_NETWORKS = [
        'solana' => 'solana_deposit_address',
        'ton'    => 'ton_deposit_address',
    ];

    public function __construct(
        private readonly HdWalletService $hdWallet,
        private readonly BlockchainDriverFactory $driverFactory,
    ) {}

    /** Returns a free hot-wallet address for the given currency. */
    public function assignDepositWallet(Currency $currency, ?Merchant $merchant = null): Wallet
    {
        // Solana / TON: ed25519 chains can't derive watch-only addresses, so we
        // use one shared public address + a unique memo per invoice.
        if (isset(self::MEMO_NETWORKS[$currency->network])) {
            return $this->memoWallet($currency, (bool) $merchant?->test_mode);
        }

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

    /**
     * Shared-address + unique-memo wallet for ed25519 chains (Solana/TON).
     * The address is the operator's public deposit wallet from settings; each
     * invoice gets a unique numeric memo for matching incoming transfers.
     */
    private function memoWallet(Currency $currency, bool $testMode): Wallet
    {
        $address = $testMode
            ? 'TEST_' . strtoupper($currency->network) . '_DEPOSIT'
            : trim((string) Setting::get(self::MEMO_NETWORKS[$currency->network], ''));

        if ($address === '') {
            throw new \RuntimeException(
                "No deposit address configured for {$currency->network}. Set it in admin settings."
            );
        }

        // Generate a memo unique among currently-active wallets for this address.
        do {
            $memo = (string) random_int(100000000, 999999999);
        } while (Wallet::where('address', $address)->where('memo', $memo)->exists());

        return Wallet::create([
            'currency_id' => $currency->id,
            'address'     => $address,
            'memo'        => $memo,
            'type'        => 'hot',
            'is_used'     => false,
        ]);
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

    /**
     * Get (or derive) a permanent static deposit wallet for a merchant + currency.
     * Reuses HD derivation; the wallet is excluded from the invoice pool.
     */
    public function staticWalletFor(Currency $currency, Merchant $merchant): Wallet
    {
        // Memo-based chains (Solana/TON) use ONE shared address — a per-merchant
        // static deposit address is impossible, so reject it cleanly.
        if (isset(self::MEMO_NETWORKS[$currency->network])) {
            throw new \RuntimeException('Static wallets are not supported for ' . $currency->network . ' (shared address + memo).');
        }

        $existing = Wallet::where('currency_id', $currency->id)
            ->where('merchant_id', $merchant->id)
            ->where('type', 'static')
            ->first();

        if ($existing) {
            return $existing;
        }

        $index = $this->nextIndex($currency);

        if ($merchant->test_mode) {
            $result = app(TestBlockchainDriver::class)->deriveAddress($index);
        } elseif ($this->hdWallet->hasXpub($this->hdNetworkKey($currency->network))) {
            $result = $this->hdWallet->deriveForCurrency($currency, $index);
        } else {
            $result = $this->driverFactory->forCurrency($currency, $merchant)->deriveAddress($index);
        }

        try {
            return $this->createFromDerivation($currency, $result, 'static', $merchant);
        } catch (\Illuminate\Database\QueryException $e) {
            // Lost a race against a concurrent request — return the existing one.
            $existing = Wallet::where('currency_id', $currency->id)
                ->where('merchant_id', $merchant->id)
                ->where('type', 'static')
                ->first();
            if ($existing) {
                return $existing;
            }
            throw $e;
        }
    }

    private function nextIndex(Currency $currency): int
    {
        // Shared index space across pool + static wallets keeps addresses unique.
        return Wallet::where('currency_id', $currency->id)->count();
    }

    private function createFromDerivation(Currency $currency, array $result, string $type = 'hot', ?Merchant $merchant = null): Wallet
    {
        return Wallet::create([
            'currency_id' => $currency->id,
            'merchant_id' => $merchant?->id,
            'address'     => $result['address'],
            'memo'        => $result['memo'] ?? null,
            'hd_path'     => $result['path'] ?? null,
            'type'        => $type,
            'is_used'     => $type === 'static', // keep static wallets out of the invoice pool
        ]);
    }

    private function hdNetworkKey(string $network): string
    {
        // All EVM chains share the same secp256k1 addresses (m/44'/60').
        return match ($network) {
            'bsc', 'arbitrum', 'optimism', 'base' => 'ethereum',
            default => $network,
        };
    }
}
