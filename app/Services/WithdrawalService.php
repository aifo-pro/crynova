<?php

namespace App\Services;

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
}
