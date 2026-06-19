<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\BalanceMovement;
use App\Models\Merchant;
use App\Services\RateService;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function index(Request $request, Merchant $merchant, RateService $rates)
    {
        $balances = $merchant->balances()->with('currency')->get();

        $movements = BalanceMovement::where('merchant_id', $merchant->id)
            ->with('currency')
            ->latest()
            ->paginate(25);

        // Sum available + locked across currencies in USD (null entries skipped).
        $totalUsd = '0';
        foreach ($balances as $bal) {
            $code = optional($bal->currency)->code;
            $price = $code ? $rates->usdPrice($code) : null;
            if ($price === null) {
                continue;
            }
            $amount = bcadd((string) $bal->available, (string) $bal->locked, 18);
            $totalUsd = bcadd($totalUsd, bcmul($amount, sprintf('%.8f', $price), 18), 18);
        }
        $totalUsd = rtrim(rtrim(number_format((float) $totalUsd, 2, '.', ''), '0'), '.') ?: '0';

        return view('merchant.balances.index', compact('merchant', 'balances', 'movements', 'totalUsd'));
    }
}
