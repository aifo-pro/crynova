<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\Merchant;
use App\Services\TelegramNotificationService;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function index(Request $request, Merchant $merchant)
    {
        $withdrawals = $merchant->withdrawals()->with('currency')->latest()->paginate(20);

        return view('merchant.withdrawals.index', compact('merchant', 'withdrawals'));
    }

    public function store(
        Request $request,
        Merchant $merchant,
        TelegramNotificationService $telegram,
        WithdrawalService $withdrawals,
    ) {
        $request->validate([
            'currency_id' => ['required', 'exists:currencies,id'],
            'amount'      => ['required', 'numeric', 'gt:0'],
            'to_address'  => ['required', 'string', 'max:100'],
            'memo'        => ['nullable', 'string', 'max:100'],
        ]);

        $currency = Currency::findOrFail($request->input('currency_id'));

        try {
            $withdrawal = $withdrawals->request(
                $merchant,
                $currency,
                (string) $request->input('amount'),
                $request->input('to_address'),
                $request->input('memo'),
            );
        } catch (\RuntimeException) {
            return back()->withErrors(['amount' => 'Insufficient balance.']);
        }

        AuditLog::record('withdrawal.requested', $withdrawal);
        $telegram->notifyWithdrawalRequested($withdrawal);

        return back()->with('success', 'Withdrawal request submitted. Admin will review it shortly.');
    }
}
