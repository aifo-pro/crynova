<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BalanceMovement;
use App\Models\Withdrawal;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    public function index()
    {
        $withdrawals = Withdrawal::with('merchant', 'currency')
            ->latest()
            ->paginate(20);

        return view('admin.withdrawals.index', compact('withdrawals'));
    }

    public function approve(Request $request, Withdrawal $withdrawal, TelegramNotificationService $telegram)
    {
        if (! $withdrawal->isPending()) {
            return back()->with('error', 'Withdrawal is not pending.');
        }

        DB::transaction(function () use ($withdrawal) {
            $balance = $withdrawal->merchant->balanceFor($withdrawal->currency);

            // Move from available to locked while processing
            $before = $balance->available;
            $after  = bcsub((string) $before, (string) $withdrawal->amount, 18);

            if (bccomp($after, '0', 18) < 0) {
                throw new \RuntimeException('Insufficient balance.');
            }

            $balance->update([
                'available' => $after,
                'locked'    => bcadd((string) $balance->locked, (string) $withdrawal->amount, 18),
            ]);

            BalanceMovement::create([
                'merchant_id'    => $withdrawal->merchant_id,
                'currency_id'    => $withdrawal->currency_id,
                'movable_id'     => $withdrawal->id,
                'movable_type'   => Withdrawal::class,
                'type'           => 'debit',
                'amount'         => $withdrawal->amount,
                'balance_before' => $before,
                'balance_after'  => $after,
                'note'           => "Withdrawal {$withdrawal->uuid} approved",
            ]);

            $withdrawal->update([
                'status'      => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            AuditLog::record('withdrawal.approved', $withdrawal);
        });

        $telegram->notifyWithdrawalReviewed($withdrawal->fresh(['merchant.user', 'currency']), 'Схвалено');

        return back()->with('success', 'Withdrawal approved and queued for processing.');
    }

    public function reject(Request $request, Withdrawal $withdrawal, TelegramNotificationService $telegram)
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $withdrawal->update([
            'status'           => 'cancelled',
            'rejection_reason' => $request->input('reason'),
        ]);

        AuditLog::record('withdrawal.rejected', $withdrawal);
        $telegram->notifyWithdrawalReviewed($withdrawal->fresh(['merchant.user', 'currency']), 'Відхилено', $request->input('reason'));

        return back()->with('success', 'Withdrawal rejected.');
    }
}
