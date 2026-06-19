<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BalanceMovement;
use App\Models\Currency;
use App\Models\Merchant;
use App\Services\BalanceService;
use App\Services\RateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/*
 * Crypto exchange between balances of one project. Rates from Binance/Bybit.
 */
class ExchangeController extends Controller
{
    /** Exchange spread fee charged by the platform. */
    private const FEE_PERCENT = '0.5';

    public function index(Request $request, RateService $rates)
    {
        $user = $request->user();
        $projects = $user->merchants()->where('status', 'active')->get();
        $currencies = Currency::where('is_active', true)->orderBy('code')->get();

        $prices = $rates->priceMap($currencies->pluck('code')->all());

        return view('account.integration.exchange', compact('projects', 'currencies', 'prices'));
    }

    public function quote(Request $request, RateService $rates): JsonResponse
    {
        $data = $request->validate([
            'from'   => ['required', 'string'],
            'to'     => ['required', 'string'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $gross = $rates->convert($data['from'], $data['to'], (string) $data['amount']);
        if ($gross === null) {
            return response()->json(['ok' => false, 'error' => 'Курс временно недоступен.'], 422);
        }

        $fee = bcmul($gross, bcdiv(self::FEE_PERCENT, '100', 18), 18);
        $net = bcsub($gross, $fee, 18);

        return response()->json([
            'ok'    => true,
            'gross' => rtrim(rtrim($gross, '0'), '.'),
            'fee'   => rtrim(rtrim($fee, '0'), '.'),
            'net'   => rtrim(rtrim($net, '0'), '.'),
        ]);
    }

    public function exchange(Request $request, RateService $rates, BalanceService $balances)
    {
        $validated = $request->validate([
            'merchant_id'      => ['required', 'integer'],
            'from_currency_id' => ['required', 'integer', 'different:to_currency_id', 'exists:currencies,id'],
            'to_currency_id'   => ['required', 'integer', 'exists:currencies,id'],
            'amount'           => ['required', 'numeric', 'gt:0'],
            'min_received'     => ['nullable', 'numeric', 'gte:0'],
        ]);

        $merchant = $request->user()->merchants()->where('id', $validated['merchant_id'])->firstOrFail();
        $from = Currency::findOrFail($validated['from_currency_id']);
        $to   = Currency::findOrFail($validated['to_currency_id']);

        $gross = $rates->convert($from->code, $to->code, (string) $validated['amount']);
        if ($gross === null) {
            return back()->with('error', __('flash.rate_unavailable'));
        }
        $fee = bcmul($gross, bcdiv(self::FEE_PERCENT, '100', 18), 18);
        $net = bcsub($gross, $fee, 18);

        // Slippage protection: reject if the rate moved below what the user accepted.
        if (isset($validated['min_received']) && bccomp($net, (string) $validated['min_received'], 18) < 0) {
            return back()->with('error', __('flash.exchange_slippage'));
        }

        try {
            DB::transaction(function () use ($merchant, $from, $to, $validated, $net, $balances) {
                $amount = (string) $validated['amount'];

                $fromBal = $balances->forMerchant($merchant, $from, lock: true);
                if (bccomp($amount, (string) $fromBal->available, 18) > 0) {
                    throw new \RuntimeException('Insufficient balance.');
                }

                $beforeF = (string) $fromBal->available;
                $afterF  = bcsub($beforeF, $amount, 18);
                $fromBal->update(['available' => $afterF]);
                BalanceMovement::create([
                    'merchant_id' => $merchant->id, 'currency_id' => $from->id,
                    'movable_id' => $merchant->id, 'movable_type' => Merchant::class,
                    'type' => 'debit', 'amount' => $amount,
                    'balance_before' => $beforeF, 'balance_after' => $afterF,
                    'note' => "Обмен {$from->code} → {$to->code}",
                ]);

                $toBal = $balances->forMerchant($merchant, $to, lock: true);
                $beforeT = (string) $toBal->available;
                $afterT  = bcadd($beforeT, $net, 18);
                $toBal->update(['available' => $afterT]);
                BalanceMovement::create([
                    'merchant_id' => $merchant->id, 'currency_id' => $to->id,
                    'movable_id' => $merchant->id, 'movable_type' => Merchant::class,
                    'type' => 'credit', 'amount' => $net,
                    'balance_before' => $beforeT, 'balance_after' => $afterT,
                    'note' => "Обмен {$from->code} → {$to->code}",
                ]);
            });
        } catch (\RuntimeException) {
            return back()->with('error', __('flash.insufficient_exchange'));
        }

        AuditLog::record('exchange.executed', $merchant, [], [
            'from' => $from->code, 'to' => $to->code,
            'amount' => $validated['amount'], 'received' => $net,
        ]);

        return back()->with('success', __('flash.exchange_done', ['amount' => $net, 'currency' => $to->code]));
    }
}
