<?php

namespace App\Services;

use App\Models\Balance;
use App\Models\Currency;
use App\Models\Merchant;

class BalanceService
{
  public function forMerchant(Merchant $merchant, Currency $currency, bool $lock = false): Balance
  {
    Balance::firstOrCreate(
      ['merchant_id' => $merchant->id, 'currency_id' => $currency->id],
      ['available' => 0, 'locked' => 0],
    );

    $query = Balance::query()
      ->where('merchant_id', $merchant->id)
      ->where('currency_id', $currency->id);

    return $lock ? $query->lockForUpdate()->firstOrFail() : $query->firstOrFail();
  }

  public function reserve(Merchant $merchant, Currency $currency, string $amount): Balance
  {
    $balance = $this->forMerchant($merchant, $currency, lock: true);

    if (bccomp($amount, (string) $balance->available, 18) > 0) {
      throw new \RuntimeException('Insufficient balance.');
    }

    $balance->update([
      'available' => bcsub((string) $balance->available, $amount, 18),
      'locked'    => bcadd((string) $balance->locked, $amount, 18),
    ]);

    return $balance->refresh();
  }

  public function release(Merchant $merchant, Currency $currency, string $amount): Balance
  {
    $balance = $this->forMerchant($merchant, $currency, lock: true);

    if (bccomp($amount, (string) $balance->locked, 18) > 0) {
      throw new \RuntimeException('Insufficient locked balance.');
    }

    $balance->update([
      'available' => bcadd((string) $balance->available, $amount, 18),
      'locked'    => bcsub((string) $balance->locked, $amount, 18),
    ]);

    return $balance->refresh();
  }
}
