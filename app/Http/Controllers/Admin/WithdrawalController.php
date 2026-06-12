<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Withdrawal;
use App\Services\TelegramNotificationService;
use App\Services\WithdrawalService;
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

    public function approve(
        Request $request,
        Withdrawal $withdrawal,
        TelegramNotificationService $telegram,
        WithdrawalService $withdrawals,
    ) {
        if (! $withdrawal->isPending()) {
            return back()->with('error', 'Withdrawal is not pending.');
        }

        DB::transaction(function () use ($withdrawal, $withdrawals) {
            $locked = Withdrawal::whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw new \RuntimeException('Withdrawal is not pending.');
            }

            $withdrawals->ensureReserved($locked);

            $locked->update([
                'status'      => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            AuditLog::record('withdrawal.approved', $locked);
        });

        $telegram->notifyWithdrawalReviewed($withdrawal->fresh(['merchant.user', 'currency']), 'Схвалено');

        return back()->with('success', 'Withdrawal approved and queued for processing.');
    }

    public function reject(
        Request $request,
        Withdrawal $withdrawal,
        TelegramNotificationService $telegram,
        WithdrawalService $withdrawals,
    ) {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        DB::transaction(function () use ($request, $withdrawal, $withdrawals) {
            $locked = Withdrawal::whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw new \RuntimeException('Withdrawal is not pending.');
            }

            $withdrawals->releaseIfReserved($locked);

            $locked->update([
                'status'           => 'cancelled',
                'rejection_reason' => $request->input('reason'),
                'funds_reserved'   => false,
            ]);

            AuditLog::record('withdrawal.rejected', $locked);
        });

        $telegram->notifyWithdrawalReviewed(
            $withdrawal->fresh(['merchant.user', 'currency']),
            'Відхилено',
            $request->input('reason'),
        );

        return back()->with('success', 'Withdrawal rejected.');
    }
}
