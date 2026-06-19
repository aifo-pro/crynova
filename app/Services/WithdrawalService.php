<?php

namespace App\Services;

use App\Models\BalanceMovement;
use App\Models\Currency;
use App\Models\Merchant;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;

class WithdrawalService
{
  public function __construct(
    private readonly BalanceService $balances,
  ) {}

  public function request(
    Merchant $merchant,
    Currency $currency,
    string $amount,
    string $toAddress,
    ?string $memo = null,
  ): Withdrawal {
    return DB::transaction(function () use ($merchant, $currency, $amount, $toAddress, $memo) {
      $this->balances->reserve($merchant, $currency, $amount);

      return Withdrawal::create([
        'merchant_id'     => $merchant->id,
        'currency_id'     => $currency->id,
        'amount'          => $amount,
        'to_address'      => $toAddress,
        'memo'            => $memo,
        'status'          => 'pending',
        'funds_reserved'  => true,
      ]);
    });
  }

  public function ensureReserved(Withdrawal $withdrawal): void
  {
    if ($withdrawal->funds_reserved) {
      return;
    }

    $balance = $this->balances->forMerchant($withdrawal->merchant, $withdrawal->currency, lock: true);
    $amount  = (string) $withdrawal->amount;

    if (bccomp($amount, (string) $balance->locked, 18) <= 0) {
      $withdrawal->update(['funds_reserved' => true]);

      return;
    }

    $this->balances->reserve($withdrawal->merchant, $withdrawal->currency, $amount);
    $withdrawal->update(['funds_reserved' => true]);
  }

  public function releaseIfReserved(Withdrawal $withdrawal): void
  {
    $amount  = (string) $withdrawal->amount;
    $balance = $this->balances->forMerchant($withdrawal->merchant, $withdrawal->currency, lock: true);

    if (! $withdrawal->funds_reserved && bccomp($amount, (string) $balance->locked, 18) > 0) {
      return;
    }

    if (bccomp((string) $balance->locked, $amount, 18) < 0) {
      return;
    }

    $this->balances->release($withdrawal->merchant, $withdrawal->currency, $amount);
  }

  /**
   * Finalize a sent withdrawal: permanently remove the reserved funds from the
   * locked balance and record the on-chain transaction. Idempotent per withdrawal.
   */
  public function complete(Withdrawal $withdrawal, ?string $txHash = null): Withdrawal
  {
    return DB::transaction(function () use ($withdrawal, $txHash) {
      $locked = Withdrawal::whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

      if (in_array($locked->status, ['sent', 'confirmed'], true)) {
        return $locked; // already finalized
      }

      $amount = (string) $locked->amount;
      $key    = 'withdrawal:' . $locked->id . ':settle';

      if (! BalanceMovement::where('idempotency_key', $key)->exists()) {
        $balance = $this->balances->forMerchant($locked->merchant, $locked->currency, lock: true);
        $before  = (string) $balance->locked;

        if ($locked->funds_reserved && bccomp($amount, $before, 18) <= 0) {
          $this->balances->settle($locked->merchant, $locked->currency, $amount);
          $after = bcsub($before, $amount, 18);

          BalanceMovement::create([
            'merchant_id'     => $locked->merchant_id,
            'currency_id'     => $locked->currency_id,
            'movable_id'      => $locked->id,
            'movable_type'    => Withdrawal::class,
            'type'            => 'debit',
            'idempotency_key' => $key,
            'amount'          => $amount,
            'balance_before'  => $before,
            'balance_after'   => $after,
            'note'            => "Withdrawal {$locked->uuid} sent" . ($txHash ? " · {$txHash}" : ''),
          ]);
        }
      }

      $locked->update([
        'status'         => 'sent',
        'tx_hash'        => $txHash ?: $locked->tx_hash,
        'amount_sent'    => $locked->amount_sent ?? $amount,
        'funds_reserved' => false,
      ]);

      return $locked;
    });
  }
}
