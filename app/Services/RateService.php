<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/*
 * Live crypto rates from Binance (primary) with Bybit fallback.
 * Returns USD prices per base asset; cross rates computed from those.
 * Stablecoins are pinned to $1. Prices cached for 60s.
 */
class RateService
{
    private const STABLE = ['USDT', 'USDC', 'USDD', 'DAI', 'TUSD', 'PYUSD', 'BUSD'];

    /** Map our currency code → Binance/Bybit base asset symbol. */
    public function baseAsset(string $code): string
    {
        // Strip network suffix: USDT_TRC20 → USDT, ETH_ARB → ETH
        return strtoupper(explode('_', $code)[0]);
    }

    /** USD price for a currency code (null if unavailable). */
    public function usdPrice(string $code): ?float
    {
        $asset = $this->baseAsset($code);

        if (in_array($asset, self::STABLE, true)) {
            return 1.0;
        }

        return Cache::remember("rate:usd:{$asset}", 60, function () use ($asset) {
            return $this->fetchBinance($asset) ?? $this->fetchBybit($asset);
        });
    }

    /** Convert an amount from one currency to another. Null if rate unavailable. */
    public function convert(string $from, string $to, string $amount): ?string
    {
        $pf = $this->usdPrice($from);
        $pt = $this->usdPrice($to);

        if ($pf === null || $pt === null || $pt <= 0) {
            return null;
        }

        // amount * priceFrom / priceTo
        $usd = bcmul($amount, $this->toBc($pf), 18);

        return bcdiv($usd, $this->toBc($pt), 18);
    }

    /** USD price map for a set of currency codes (for client-side estimates). */
    public function priceMap(array $codes): array
    {
        $map = [];
        foreach (array_unique($codes) as $code) {
            $map[$code] = $this->usdPrice($code);
        }

        return $map;
    }

    // ── Providers ──────────────────────────────────────────────────
    private function fetchBinance(string $asset): ?float
    {
        try {
            $res = Http::timeout(8)->withoutVerifying()
                ->get('https://api.binance.com/api/v3/ticker/price', ['symbol' => $asset . 'USDT']);
            if ($res->successful() && isset($res->json()['price'])) {
                return (float) $res->json()['price'];
            }
        } catch (\Throwable) {
            // fall through to fallback
        }

        return null;
    }

    private function fetchBybit(string $asset): ?float
    {
        try {
            $res = Http::timeout(8)->withoutVerifying()
                ->get('https://api.bybit.com/v5/market/tickers', [
                    'category' => 'spot',
                    'symbol'   => $asset . 'USDT',
                ]);
            $list = $res->json()['result']['list'][0]['lastPrice'] ?? null;
            if ($list !== null) {
                return (float) $list;
            }
        } catch (\Throwable) {
            // unavailable
        }

        return null;
    }

    /** Float → bc-safe decimal string (avoids scientific notation). */
    private function toBc(float $v): string
    {
        return number_format($v, 18, '.', '');
    }

    // ── Fiat ───────────────────────────────────────────────────────────
    /** Is this code one of the supported fiat currencies? */
    public function isFiat(string $code): bool
    {
        return in_array(strtoupper(trim($code)), (array) config('crynova.fiat_currencies', []), true);
    }

    /** Live fiat rates per 1 USD (e.g. ['UAH'=>45.0, 'EUR'=>0.92]). Cached 1h. */
    public function fiatRates(): array
    {
        return Cache::remember('rate:fiat:usd', 3600, function () {
            try {
                $res = Http::timeout(10)->withoutVerifying()->get((string) config('crynova.fiat_rates_url'));
                $rates = $res->json()['rates'] ?? null;
                if (is_array($rates)) {
                    return $rates;
                }
            } catch (\Throwable) {
                // fall through
            }

            return [];
        });
    }

    /** USD value of a fiat amount (null if rate unavailable). */
    public function fiatToUsd(string $fiat, string $amount): ?string
    {
        $fiat = strtoupper(trim($fiat));
        if ($fiat === 'USD') {
            return $amount;
        }

        $rate = $this->fiatRates()[$fiat] ?? null; // units of $fiat per 1 USD
        if ($rate === null || (float) $rate <= 0) {
            return null;
        }

        // usd = amount / (units per usd)
        return bcdiv($amount, $this->toBc((float) $rate), 18);
    }

    /** Convert a fiat amount into a crypto amount via USD. Null if unavailable. */
    public function convertFiatToCrypto(string $fiat, string $cryptoCode, string $amount): ?string
    {
        $usd = $this->fiatToUsd($fiat, $amount);
        $cryptoPrice = $this->usdPrice($cryptoCode);

        if ($usd === null || $cryptoPrice === null || $cryptoPrice <= 0) {
            return null;
        }

        // crypto = usd_value / crypto_usd_price
        return bcdiv($usd, $this->toBc($cryptoPrice), 18);
    }
}
