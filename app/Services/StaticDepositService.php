<?php

namespace App\Services;

use App\Models\BalanceMovement;
use App\Models\Wallet;
use App\Services\Blockchain\BlockchainDriverFactory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Watches merchant static (permanent) deposit wallets, credits confirmed
 * incoming transfers to the merchant balance and fires a wallet.deposit
 * webhook. Crediting is idempotent per (wallet, tx_hash).
 */
class StaticDepositService
{
    public function __construct(
        private readonly BlockchainDriverFactory $driverFactory,
        private readonly BalanceService $balances,
        private readonly WebhookService $webhooks,
    ) {}

    public function scan(): void
    {
        Wallet::with('currency', 'merchant')
            ->where('type', 'static')
            ->whereNotNull('merchant_id')
            ->chunkById(100, function ($wallets) {
                foreach ($wallets as $wallet) {
                    $this->scanWallet($wallet);
                }
            });
    }

    public function scanWallet(Wallet $wallet): void
    {
        $currency = $wallet->currency;
        $merchant = $wallet->merchant;
        if (! $currency || ! $merchant) {
            return;
        }

        try {
            $driver = $this->driverFactory->forCurrency($currency, $merchant);
            $txs = $driver->getTransactions($wallet->address, 0, $currency);
        } catch (\Throwable $e) {
            Log::warning("Static deposit scan failed for wallet {$wallet->id}: " . $e->getMessage());
            return;
        }

        $required = max((int) $currency->confirmations_required, 1);

        $addr = strtolower($wallet->address);

        foreach ($txs as $tx) {
            $txHash = $tx['txid'] ?? $tx['tx_hash'] ?? null;
            $amount = (string) ($tx['amount'] ?? '0');
            $confs  = (int) ($tx['confirmations'] ?? 0);

            if (! $txHash || bccomp($amount, '0', 18) <= 0) {
                continue;
            }
            // Only credit genuine incoming transfers to this address.
            if (isset($tx['direction']) && $tx['direction'] !== 'incoming') {
                continue;
            }
            if (isset($tx['to']) && strtolower((string) $tx['to']) !== $addr) {
                continue;
            }
            if ($confs < $required) {
                continue; // wait for enough confirmations
            }

            $this->credit($wallet, $txHash, $amount, $confs);
        }
    }

    private function credit(Wallet $wallet, string $txHash, string $amount, int $confs): void
    {
        $key = 'static:' . $wallet->id . ':' . $txHash;

        if (BalanceMovement::where('idempotency_key', $key)->exists()) {
            return; // already credited
        }

        DB::transaction(function () use ($wallet, $txHash, $amount, $confs, $key) {
            $merchant = $wallet->merchant;
            $currency = $wallet->currency;

            $balance = $this->balances->forMerchant($merchant, $currency, lock: true);
            $amlHold = (bool) ($merchant->aml_enabled ?? false);

            if ($amlHold) {
                $before = (string) $balance->locked;
                $after  = bcadd($before, $amount, 18);
                $balance->update(['locked' => $after]);
            } else {
                $before = (string) $balance->available;
                $after  = bcadd($before, $amount, 18);
                $balance->update(['available' => $after]);
            }

            try {
                BalanceMovement::create([
                    'merchant_id'     => $merchant->id,
                    'currency_id'     => $currency->id,
                    'movable_id'      => $wallet->id,
                    'movable_type'    => Wallet::class,
                    'type'            => $amlHold ? 'hold' : 'credit',
                    'idempotency_key' => $key,
                    'amount'          => $amount,
                    'balance_before'  => $before,
                    'balance_after'   => $after,
                    'note'            => $amlHold ? "Static wallet deposit {$txHash} · AML hold" : "Static wallet deposit {$txHash}",
                ]);
            } catch (QueryException $e) {
                if ($this->isDuplicateKey($e)) {
                    return; // concurrent credit — safe to ignore
                }
                throw $e;
            }

            DB::afterCommit(function () use ($merchant, $wallet, $currency, $amount, $txHash, $confs) {
                $this->webhooks->dispatchMerchantEvent($merchant, 'wallet.deposit', [
                    'currency'      => $currency->code,
                    'network'       => $currency->network,
                    'address'       => $wallet->address,
                    'amount'        => $amount,
                    'tx_hash'       => $txHash,
                    'confirmations' => $confs,
                ]);
            });
        });
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        return ($e->errorInfo[1] ?? null) === 1062;
    }
}
