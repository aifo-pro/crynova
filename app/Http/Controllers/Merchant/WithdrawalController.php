<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\Merchant;
use App\Models\Withdrawal;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function index(Request $request, Merchant $merchant)
    {
        $withdrawals = $merchant->withdrawals()->with('currency')->latest()->paginate(20);

        return view('merchant.withdrawals.index', compact('merchant', 'withdrawals'));
    }

    public function store(Request $request, Merchant $merchant, TelegramNotificationService $telegram)
    {
        $request->validate([
            'currency_id' => ['required', 'exists:currencies,id'],
            'amount'      => ['required', 'numeric', 'gt:0'],
            'to_address'  => ['required', 'string', 'max:100'],
            'memo'        => ['nullable', 'string', 'max:100'],
        ]);

        $currency = Currency::findOrFail($request->input('currency_id'));
        $balance  = $merchant->balanceFor($currency);

        if (bccomp((string) $request->input('amount'), (string) $balance->available, 18) > 0) {
            return back()->withErrors(['amount' => 'Insufficient balance.']);
        }

        $withdrawal = Withdrawal::create([
            'merchant_id' => $merchant->id,
            'currency_id' => $currency->id,
            'amount'      => $request->input('amount'),
            'to_address'  => $request->input('to_address'),
            'memo'        => $request->input('memo'),
            'status'      => 'pending',
        ]);

        AuditLog::record('withdrawal.requested', $withdrawal);
        $telegram->notifyWithdrawalRequested($withdrawal);

        return back()->with('success', 'Withdrawal request submitted. Admin will review it shortly.');
    }
}
