<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\BalanceMovement;
use App\Models\Merchant;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function index(Request $request, Merchant $merchant)
    {
        $balances = $merchant->balances()->with('currency')->get();

        $movements = BalanceMovement::where('merchant_id', $merchant->id)
            ->with('currency')
            ->latest()
            ->paginate(25);

        $totalUsd = null; // placeholder — wire to exchange rate service

        return view('merchant.balances.index', compact('merchant', 'balances', 'movements', 'totalUsd'));
    }
}
