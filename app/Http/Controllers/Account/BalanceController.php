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

        // Aggregate balances per currency
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

        // Data for the other tabs
        $projects     = $user->merchants()->where('status', 'active')->get();
        $withdrawals  = Withdrawal::whereIn('merchant_id', $merchantIds)->with('currency', 'merchant')->latest()->limit(20)->get();
        $addresses    = $user->savedAddresses()->with('currency')->latest()->get();
        $autoRules    = AutoWithdrawRule::whereIn('merchant_id', $merchantIds)->with('currency', 'merchant')->get();
        $allCurrencies = Currency::where('is_active', true)->orderBy('code')->get();

        return view('account.balance', compact(
            'currencies', 'movements', 'projects', 'withdrawals', 'addresses', 'autoRules', 'allCurrencies'
        ));
    }

    // ── Вывод средств ──────────────────────────────────────────────
    public function withdraw(Request $request, TelegramNotificationService $telegram)
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
        $balance  = $merchant->balanceFor($currency);

        if (bccomp((string) $validated['amount'], (string) $balance->available, 18) > 0) {
            return back()->with('error', 'Недостатньо коштів на балансі проєкту.');
        }

        $withdrawal = DB::transaction(function () use ($merchant, $currency, $validated, $balance) {
            // Lock the funds (move available → locked) until admin processes it
            $balance->update([
                'available' => bcsub((string) $balance->available, (string) $validated['amount'], 18),
                'locked'    => bcadd((string) $balance->locked, (string) $validated['amount'], 18),
            ]);

            $w = Withdrawal::create([
                'merchant_id' => $merchant->id,
                'currency_id' => $currency->id,
                'amount'      => $validated['amount'],
                'to_address'  => $validated['to_address'],
                'memo'        => $validated['memo'] ?? null,
                'status'      => 'pending',
            ]);

            AuditLog::record('withdrawal.requested', $w);

            return $w;
        });

        $telegram->notifyWithdrawalRequested($withdrawal);

        return back()->with('success', 'Заявку на виведення створено та надіслано на перевірку.');
    }

    // ── Массовые выплаты ───────────────────────────────────────────
    public function massPayout(Request $request, TelegramNotificationService $telegram)
    {
        $validated = $request->validate([
            'merchant_id' => ['required', 'integer'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'rows'        => ['required', 'string'], // one "address,amount[,memo]" per line
        ]);

        $merchant = $this->ownedMerchant($request, $validated['merchant_id']);
        $currency = Currency::findOrFail($validated['currency_id']);
        $balance  = $merchant->balanceFor($currency);

        // Parse lines
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
        if (bccomp($total, (string) $balance->available, 18) > 0) {
            return back()->with('error', "Недостаточно средств: нужно {$total}, доступно {$balance->available}.");
        }

        $withdrawalIds = DB::transaction(function () use ($merchant, $currency, $payouts, $total, $balance) {
            $balance->update([
                'available' => bcsub((string) $balance->available, $total, 18),
                'locked'    => bcadd((string) $balance->locked, $total, 18),
            ]);

            $ids = [];

            foreach ($payouts as $p) {
                $w = Withdrawal::create([
                    'merchant_id' => $merchant->id,
                    'currency_id' => $currency->id,
                    'amount'      => $p['amount'],
                    'to_address'  => $p['address'],
                    'memo'        => $p['memo'],
                    'status'      => 'pending',
                ]);
                AuditLog::record('withdrawal.requested', $w, [], ['batch' => true]);
                $ids[] = $w->id;
            }

            return $ids;
        });

        Withdrawal::with('merchant.user', 'currency')
            ->whereIn('id', $withdrawalIds)
            ->get()
            ->each(fn (Withdrawal $withdrawal) => $telegram->notifyWithdrawalRequested($withdrawal));

        return back()->with('success', count($payouts) . ' виплат(и) створено та надіслано на перевірку.');
    }

    // ── Сохранённые адреса ─────────────────────────────────────────
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

    // ── Настройки автовывода ───────────────────────────────────────
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
