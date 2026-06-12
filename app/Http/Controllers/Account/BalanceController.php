<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\AutoWithdrawRule;
use App\Models\Balance;
use App\Models\BalanceMovement;
use App\Models\Currency;
use App\Models\Merchant;
use App\Models\SavedAddress;
use App\Models\Withdrawal;
use App\Services\TelegramNotificationService;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/*
 * Account-level balance: assets, withdrawals, mass payouts, saved addresses,
 * auto-withdraw rules — aggregated across the user's projects.
 */
class BalanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $merchantIds = $user->merchants()->pluck('id');

        $rows = Balance::whereIn('merchant_id', $merchantIds)->get();
        $byCurrency = $rows->groupBy('currency_id')->map(fn ($g) => [
            'available' => (string) $g->sum('available'),
            'locked'    => (string) $g->sum('locked'),
        ]);

        $currencies = Currency::where('is_active', true)->orderBy('code')->get()->map(function ($c) use ($byCurrency) {
            $bal = $byCurrency[$c->id] ?? ['available' => '0', 'locked' => '0'];
            $c->bal_available = $bal['available'];
            $c->bal_locked    = $bal['locked'];
            return $c;
        });

        $movements = BalanceMovement::whereIn('merchant_id', $merchantIds)
            ->with('currency', 'merchant')
            ->when($request->input('search'), fn ($q, $s) => $q->where('note', 'like', "%{$s}%"))
            ->when($request->input('currency'), fn ($q, $c) => $q->where('currency_id', $c))
            ->when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $projects     = $user->merchants()->where('status', 'active')->get();
        $withdrawals  = Withdrawal::whereIn('merchant_id', $merchantIds)->with('currency', 'merchant')->latest()->limit(20)->get();
        $addresses    = $user->savedAddresses()->with('currency')->latest()->get();
        $autoRules    = AutoWithdrawRule::whereIn('merchant_id', $merchantIds)->with('currency', 'merchant')->get();
        $allCurrencies = Currency::where('is_active', true)->orderBy('code')->get();

        return view('account.balance', compact(
            'currencies', 'movements', 'projects', 'withdrawals', 'addresses', 'autoRules', 'allCurrencies'
        ));
    }

    public function withdraw(Request $request, TelegramNotificationService $telegram, WithdrawalService $withdrawals)
    {
        $validated = $request->validate([
            'merchant_id' => ['required', 'integer'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'amount'      => ['required', 'numeric', 'gt:0'],
            'to_address'  => ['required', 'string', 'max:255'],
            'memo'        => ['nullable', 'string', 'max:255'],
        ]);

        $merchant = $this->ownedMerchant($request, $validated['merchant_id']);
        $currency = Currency::findOrFail($validated['currency_id']);

        try {
            $withdrawal = $withdrawals->request(
                $merchant,
                $currency,
                (string) $validated['amount'],
                $validated['to_address'],
                $validated['memo'] ?? null,
            );
        } catch (\RuntimeException) {
            return back()->with('error', 'Недостатньо коштів на балансі проєкту.');
        }

        AuditLog::record('withdrawal.requested', $withdrawal);
        $telegram->notifyWithdrawalRequested($withdrawal);

        return back()->with('success', 'Заявку на виведення створено та надіслано на перевірку.');
    }

    public function massPayout(Request $request, TelegramNotificationService $telegram, WithdrawalService $withdrawals)
    {
        $validated = $request->validate([
            'merchant_id' => ['required', 'integer'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'rows'        => ['required', 'string'],
        ]);

        $merchant = $this->ownedMerchant($request, $validated['merchant_id']);
        $currency = Currency::findOrFail($validated['currency_id']);

        $payouts = [];
        $total = '0';
        foreach (preg_split('/\r\n|\r|\n/', trim($validated['rows'])) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $parts = array_map('trim', explode(',', $line));
            $addr = $parts[0] ?? '';
            $amt  = $parts[1] ?? '';
            if ($addr === '' || ! is_numeric($amt) || bccomp($amt, '0', 18) <= 0) {
                return back()->with('error', "Некорректная строка: {$line}");
            }
            $payouts[] = ['address' => $addr, 'amount' => $amt, 'memo' => $parts[2] ?? null];
            $total = bcadd($total, $amt, 18);
        }

        if (empty($payouts)) {
            return back()->with('error', 'Не вказано жодної виплати.');
        }

        try {
            $withdrawalIds = DB::transaction(function () use ($merchant, $currency, $payouts, $withdrawals) {
                $ids = [];

                foreach ($payouts as $p) {
                    $w = $withdrawals->request(
                        $merchant,
                        $currency,
                        $p['amount'],
                        $p['address'],
                        $p['memo'],
                    );
                    AuditLog::record('withdrawal.requested', $w, [], ['batch' => true]);
                    $ids[] = $w->id;
                }

                return $ids;
            });
        } catch (\RuntimeException) {
            return back()->with('error', 'Недостатньо коштів для масової виплати.');
        }

        Withdrawal::with('merchant.user', 'currency')
            ->whereIn('id', $withdrawalIds)
            ->get()
            ->each(fn (Withdrawal $withdrawal) => $telegram->notifyWithdrawalRequested($withdrawal));

        return back()->with('success', count($payouts) . ' виплат(и) створено та надіслано на перевірку.');
    }

    public function storeAddress(Request $request)
    {
        $validated = $request->validate([
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'label'       => ['required', 'string', 'max:100'],
            'address'     => ['required', 'string', 'max:255'],
            'memo'        => ['nullable', 'string', 'max:255'],
        ]);
        $validated['user_id'] = $request->user()->id;

        SavedAddress::create($validated);

        return back()->with('success', 'Адресу збережено.');
    }

    public function destroyAddress(Request $request, SavedAddress $address)
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $address->delete();

        return back()->with('success', 'Адресу видалено.');
    }

    public function autoWithdraw(Request $request)
    {
        $validated = $request->validate([
            'merchant_id' => ['required', 'integer'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'address'     => ['required', 'string', 'max:255'],
            'memo'        => ['nullable', 'string', 'max:255'],
            'min_amount'  => ['required', 'numeric', 'gt:0'],
            'is_enabled'  => ['nullable', 'boolean'],
        ]);

        $merchant = $this->ownedMerchant($request, $validated['merchant_id']);

        AutoWithdrawRule::updateOrCreate(
            ['merchant_id' => $merchant->id, 'currency_id' => $validated['currency_id']],
            [
                'address'    => $validated['address'],
                'memo'       => $validated['memo'] ?? null,
                'min_amount' => $validated['min_amount'],
                'is_enabled' => $request->boolean('is_enabled'),
            ]
        );
        AuditLog::record('auto_withdraw.saved', $merchant);

        return back()->with('success', 'Правило автовиведення збережено.');
    }

    public function destroyAutoWithdraw(Request $request, AutoWithdrawRule $rule)
    {
        abort_unless($request->user()->merchants()->whereKey($rule->merchant_id)->exists(), 403);
        $rule->delete();

        return back()->with('success', 'Правило видалено.');
    }

    private function ownedMerchant(Request $request, int $id): Merchant
    {
        return $request->user()->merchants()->where('id', $id)->firstOrFail();
    }
}
