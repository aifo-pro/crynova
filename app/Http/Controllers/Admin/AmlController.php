<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Balance;
use App\Models\BalanceMovement;
use App\Models\Currency;
use App\Models\Merchant;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AmlController extends Controller
{
    /**
     * Funds currently on hold (locked balances) across all merchants.
     */
    public function index()
    {
        $holds = Balance::with('merchant', 'currency')
            ->where('locked', '>', 0)
            ->get()
            ->sortByDesc('locked')
            ->values();

        $totalHeld = $holds->sum(fn ($b) => (float) $b->locked);

        return view('admin.aml', compact('holds', 'totalHeld'));
    }

    /**
     * Release held funds: move locked → available for a merchant/currency.
     */
    public function release(Request $request, BalanceService $balances)
    {
        $data = $request->validate([
            'merchant_id' => ['required', 'exists:merchants,id'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'amount'      => ['required', 'regex:/^\d+(\.\d{1,18})?$/'],
            'reason'      => ['required', 'string', 'max:500'],
        ]);

        if (bccomp($data['amount'], '0', 18) <= 0) {
            return back()->withErrors(['amount' => 'Сума має бути більшою за нуль.']);
        }

        $merchant = Merchant::findOrFail($data['merchant_id']);
        $currency = Currency::findOrFail($data['currency_id']);

        try {
            DB::transaction(function () use ($merchant, $currency, $data, $balances) {
                $balance = $balances->forMerchant($merchant, $currency, lock: true);

                if (bccomp($data['amount'], (string) $balance->locked, 18) > 0) {
                    throw new \RuntimeException('Сума перевищує заблокований баланс.');
                }

                $beforeAvail = (string) $balance->available;
                $afterAvail  = bcadd($beforeAvail, $data['amount'], 18);

                $balance->update([
                    'available' => $afterAvail,
                    'locked'    => bcsub((string) $balance->locked, $data['amount'], 18),
                ]);

                BalanceMovement::create([
                    'merchant_id'     => $merchant->id,
                    'currency_id'     => $currency->id,
                    'type'            => 'release',
                    'idempotency_key' => 'aml-release:' . $merchant->id . ':' . $currency->id . ':' . now()->timestamp,
                    'amount'          => $data['amount'],
                    'balance_before'  => $beforeAvail,
                    'balance_after'   => $afterAvail,
                    'note'            => 'AML-розблокування: ' . $data['reason'],
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        AuditLog::record('aml.funds_released', $merchant, [], [
            'currency' => $currency->code,
            'amount'   => $data['amount'],
            'reason'   => $data['reason'],
        ], 'admin');

        return back()->with('success', 'Кошти розблоковано.');
    }
}
