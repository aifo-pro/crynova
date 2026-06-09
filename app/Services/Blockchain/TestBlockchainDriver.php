<?php

namespace App\Services\Blockchain;

use App\Models\Currency;
use App\Services\BlockchainDriverInterface;
use Illuminate\Support\Facades\Cache;

class TestBlockchainDriver implements BlockchainDriverInterface
{
    public const CACHE_PREFIX = 'crynova:test_payment:';

    public function deriveAddress(int $index): array
    {
        return [
            'address' => 'crytest_' . $index . '_' . substr(md5((string) $index), 0, 8),
            'path'    => "test/0/{$index}",
            'memo'    => null,
        ];
    }

    public function getBalance(string $address): string
    {
        return $this->pendingSimulation($address)['amount'] ?? '0';
    }

    public function getTransactions(string $address, int $fromBlock = 0, ?Currency $currency = null): array
    {
        $sim = $this->pendingSimulation($address);

        if (! $sim) {
            return [];
        }

        return [[
            'txid'          => $sim['tx_hash'],
            'tx_hash'       => $sim['tx_hash'],
            'amount'        => $sim['amount'],
            'confirmations' => (int) ($sim['confirmations'] ?? 100),
            'from'          => 'crytest_sender',
            'blockindex'    => 1,
            'blocktime'     => time(),
        ]];
    }

    public function broadcast(string $rawTx): string
    {
        return 'crytest_tx_' . substr(md5($rawTx), 0, 16);
    }

    public function getBlockHeight(): int
    {
        return 1_000_000;
    }

    /** Queue a simulated incoming payment for a test-mode deposit address. */
    public static function queuePayment(string $address, string $amount, int $confirmations = 100): void
    {
        Cache::put(self::CACHE_PREFIX . $address, [
            'tx_hash'       => 'crytest_' . uniqid(),
            'amount'        => $amount,
            'confirmations' => $confirmations,
        ], now()->addHours(2));
    }

    public static function clearPayment(string $address): void
    {
        Cache::forget(self::CACHE_PREFIX . $address);
    }

    private function pendingSimulation(string $address): ?array
    {
        return Cache::get(self::CACHE_PREFIX . $address);
    }
}
