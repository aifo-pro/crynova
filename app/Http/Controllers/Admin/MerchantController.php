<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BalanceMovement;
use App\Models\Currency;
use App\Models\Merchant;
use App\Services\BalanceService;
use App\Services\TelegramNotificationService;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MerchantController extends Controller
{
    public function index(Request $request)
    {
        $merchants = Merchant::with('user')
            ->withCount('invoices')
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('search'), function ($q, $s) {
                $q->where(function ($query) use ($s) {
                    $query->where('name', 'like', "%{$s}%")
                        ->orWhere('domain', 'like', "%{$s}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', "%{$s}%"));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $pendingCount = Merchant::where('status', Merchant::STATUS_MODERATION)->count();
        $stats = [
            'total' => Merchant::count(),
            'active' => Merchant::where('status', Merchant::STATUS_ACTIVE)->count(),
            'moderation' => $pendingCount,
            'blocked' => Merchant::where('status', Merchant::STATUS_BLOCKED)->count(),
        ];

        return view('admin.merchants.index', compact('merchants', 'pendingCount', 'stats'));
    }

    public function show(Merchant $merchant)
    {
        $merchant->load([
            'user',
            'apiKeys',
            'balances.currency',
            'moderatedBy',
            'currencies',
            'paymentLinks.currency',
        ]);

        $invoices = $merchant->invoices()->with('currency')->latest()->paginate(15);
        $analytics = $this->buildAnalytics($merchant);
        $balanceSummary = $this->buildBalanceSummary($merchant);
        $businessTypes = $this->businessTypeLabels();
        $allCurrencies = Currency::where('is_active', true)->orderBy('code')->get();
        $enabledCurrencyIds = $merchant->currencies()->pluck('currencies.id')->all();
        $invoiceCount = $merchant->invoices()->count();
        $baseCurrencyOptions = Currency::where('is_active', true)
            ->select('code')
            ->distinct()
            ->orderBy('code')
            ->pluck('code')
            ->prepend('USD')
            ->unique()
            ->values();

        return view('admin.merchants.show', compact(
            'merchant',
            'invoices',
            'analytics',
            'balanceSummary',
            'businessTypes',
            'allCurrencies',
            'enabledCurrencyIds',
            'invoiceCount',
            'baseCurrencyOptions',
        ));
    }

    public function approve(Request $request, Merchant $merchant, TelegramNotificationService $telegram)
    {
        $old = ['status' => $merchant->status];

        $merchant->update([
            'status'       => Merchant::STATUS_ACTIVE,
            'is_active'    => true,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
            'reject_reason'=> null,
        ]);

        AuditLog::record('merchant.approved', $merchant, $old, ['status' => $merchant->status]);
        $telegram->notifyMerchantStatus($merchant, 'Активний');

        return back()->with('success', __('flash.merchant_approved', ['name' => $merchant->name]));
    }

    public function reject(Request $request, Merchant $merchant, TelegramNotificationService $telegram)
    {
        $request->validate(['reject_reason' => ['required', 'string', 'max:500']]);

        $old = ['status' => $merchant->status];

        $merchant->update([
            'status'        => Merchant::STATUS_REJECTED,
            'is_active'     => false,
            'moderated_by'  => $request->user()->id,
            'moderated_at'  => now(),
            'reject_reason' => $request->input('reject_reason'),
        ]);

        AuditLog::record('merchant.rejected', $merchant, $old, ['status' => $merchant->status]);
        $telegram->notifyMerchantStatus($merchant, 'Відхилено', $request->input('reject_reason'));

        return back()->with('success', __('flash.merchant_rejected', ['name' => $merchant->name]));
    }

    public function block(Request $request, Merchant $merchant, TelegramNotificationService $telegram)
    {
        $old = ['status' => $merchant->status];

        if ($merchant->isBlocked()) {
            $merchant->update(['status' => Merchant::STATUS_ACTIVE, 'is_active' => true]);
            $action = 'merchant.unblocked';
            $msg = "Мерчанта «{$merchant->name}» розблоковано.";
        } else {
            $merchant->update(['status' => Merchant::STATUS_BLOCKED, 'is_active' => false]);
            $action = 'merchant.blocked';
            $msg = "Мерчанта «{$merchant->name}» заблоковано.";
        }

        AuditLog::record($action, $merchant, $old, ['status' => $merchant->status]);
        $telegram->notifyMerchantStatus($merchant, $merchant->isBlocked() ? 'Заблоковано' : 'Активний');

        return back()->with('success', $msg);
    }

    public function updateNote(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $merchant->update($validated);
        AuditLog::record('merchant.admin_note_updated', $merchant);

        return back()->with('success', __('flash.note_saved'));
    }

    public function updateDescription(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'project_description' => ['required', 'string', 'max:1000'],
        ]);

        $merchant->update($validated);
        AuditLog::record('merchant.description_updated', $merchant);

        return back()->with('success', __('flash.project_desc_saved'));
    }

    public function updateBaseCurrency(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'base_currency_code' => ['required', 'string', 'max:10'],
        ]);

        $merchant->update($validated);
        AuditLog::record('merchant.base_currency_updated', $merchant);

        return back()->with('success', __('flash.base_currency_updated'));
    }

    public function updateLimits(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'max_invoice_amount'     => ['nullable', 'numeric', 'min:0'],
            'daily_turnover_limit'   => ['nullable', 'numeric', 'min:0'],
            'monthly_turnover_limit' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach (['max_invoice_amount', 'daily_turnover_limit', 'monthly_turnover_limit'] as $field) {
            $value = $validated[$field] ?? null;
            $validated[$field] = ($value === null || (float) $value <= 0) ? null : $value;
        }

        $merchant->update($validated);
        AuditLog::record('merchant.limits_updated', $merchant);

        return back()->with('success', __('flash.limits_saved'));
    }

    public function updatePaymentMethods(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'currencies'   => ['array'],
            'currencies.*' => ['integer', 'exists:currencies,id'],
        ]);

        $merchant->currencies()->sync(
            collect($validated['currencies'] ?? [])->mapWithKeys(fn ($id) => [$id => ['is_enabled' => true]])->all()
        );

        AuditLog::record('merchant.currencies_updated', $merchant);

        return back()->with('success', __('flash.payment_methods_updated'));
    }

    public function testWebhook(Merchant $merchant, WebhookService $webhooks)
    {
        $result = $webhooks->sendTest($merchant);

        return back()->with(
            $result['success'] ? 'success' : 'danger',
            $result['message']
        );
    }

    public function rotateSecret(Merchant $merchant)
    {
        $raw = Str::random(40);
        $merchant->update(['webhook_secret' => $raw]);
        AuditLog::record('merchant.secret_regenerated', $merchant);

        return back()
            ->with('success', __('flash.webhook_secret_updated'))
            ->with('new_webhook_secret', $raw);
    }

    public function destroy(Request $request, Merchant $merchant)
    {
        $request->validate([
            'confirm_name' => ['required', 'string'],
        ]);

        if ($request->input('confirm_name') !== $merchant->name) {
            return back()->withErrors(['confirm_name' => 'Назва не співпадає. Введіть точну назву мерчанта.']);
        }

        AuditLog::record('merchant.deleted_by_admin', $merchant);
        $name = $merchant->name;
        $merchant->delete();

        return redirect()
            ->route('admin.merchants.index')
            ->with('success', __('flash.cashbox_deleted', ['name' => $name]));
    }

    private function buildAnalytics(Merchant $merchant): array
    {
        $todayStart = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        $paidStatuses = ['paid', 'overpaid'];
        $failedStatuses = ['expired', 'failed', 'refunded'];

        $turnoverToday = (float) $merchant->invoices()
            ->whereIn('status', $paidStatuses)
            ->where('paid_at', '>=', $todayStart)
            ->sum('net_amount');

        $turnoverMonth = (float) $merchant->invoices()
            ->whereIn('status', $paidStatuses)
            ->where('paid_at', '>=', $monthStart)
            ->sum('net_amount');

        $totalPayments = $merchant->invoices()->count();
        $successful = $merchant->invoices()->whereIn('status', $paidStatuses)->count();
        $unsuccessful = $merchant->invoices()->whereIn('status', $failedStatuses)->count();

        $dailyRows = $merchant->invoices()
            ->selectRaw("DATE(created_at) as day,
                SUM(CASE WHEN status IN ('paid','overpaid') THEN COALESCE(net_amount, 0) ELSE 0 END) as revenue")
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $chartLabels = [];
        $chartRevenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->format('d.m');
            $chartRevenue[] = round((float) ($dailyRows->get($day)->revenue ?? 0), 4);
        }

        $currencyDistribution = $merchant->invoices()
            ->join('currencies', 'payment_invoices.currency_id', '=', 'currencies.id')
            ->whereIn('payment_invoices.status', $paidStatuses)
            ->selectRaw('currencies.code, COUNT(*) as cnt, SUM(COALESCE(payment_invoices.net_amount, 0)) as total')
            ->groupBy('currencies.code')
            ->orderByDesc('cnt')
            ->get();

        return compact(
            'turnoverToday',
            'turnoverMonth',
            'totalPayments',
            'successful',
            'unsuccessful',
            'chartLabels',
            'chartRevenue',
            'currencyDistribution',
        );
    }

    /**
     * Manual credit/debit of a merchant balance with a mandatory reason.
     * Records a BalanceMovement (audit trail) and an admin AuditLog entry.
     */
    public function adjustBalance(Request $request, Merchant $merchant, BalanceService $balances)
    {
        $data = $request->validate([
            'currency_id' => ['required', 'exists:currencies,id'],
            'direction'   => ['required', 'in:credit,debit'],
            'amount'      => ['required', 'regex:/^\d+(\.\d{1,18})?$/'],
            'reason'      => ['required', 'string', 'max:500'],
        ]);

        if (bccomp($data['amount'], '0', 18) <= 0) {
            return back()->withErrors(['amount' => 'Сума має бути більшою за нуль.']);
        }

        $currency = Currency::findOrFail($data['currency_id']);

        try {
            DB::transaction(function () use ($merchant, $currency, $data, $balances) {
                $balance = $balances->forMerchant($merchant, $currency, lock: true);
                $before  = (string) $balance->available;

                if ($data['direction'] === 'debit' && bccomp($data['amount'], $before, 18) > 0) {
                    throw new \RuntimeException('Недостатньо доступних коштів для списання.');
                }

                $after = $data['direction'] === 'credit'
                    ? bcadd($before, $data['amount'], 18)
                    : bcsub($before, $data['amount'], 18);

                $balance->update(['available' => $after]);

                BalanceMovement::create([
                    'merchant_id'     => $merchant->id,
                    'currency_id'     => $currency->id,
                    'type'            => 'adjustment',
                    'idempotency_key' => 'adjust:' . $merchant->id . ':' . $currency->id . ':' . now()->timestamp . ':' . Str::random(8),
                    'amount'          => ($data['direction'] === 'debit' ? '-' : '') . $data['amount'],
                    'balance_before'  => $before,
                    'balance_after'   => $after,
                    'note'            => 'Ручна корекція (' . $data['direction'] . '): ' . $data['reason'],
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        AuditLog::record('merchant.balance_adjusted', $merchant, [], [
            'currency'  => $currency->code,
            'direction' => $data['direction'],
            'amount'    => $data['amount'],
            'reason'    => $data['reason'],
        ], 'admin');

        return back()->with('success', 'Баланс мерчанта скориговано.');
    }

    private function buildBalanceSummary(Merchant $merchant): array
    {
        $available = (float) $merchant->balances->sum('available');
        $blocked = (float) $merchant->balances->sum('locked');

        $processing = (float) $merchant->invoices()
            ->whereIn('status', ['pending', 'waiting_confirmations'])
            ->sum('amount');

        return [
            'available'  => $available,
            'processing' => $processing,
            'blocked'    => $blocked,
        ];
    }

    private function businessTypeLabels(): array
    {
        return collect([
            'ecommerce', 'online_school', 'service', 'digital_goods',
            'telegram_bot', 'saas', 'donations', 'other',
        ])->mapWithKeys(fn ($type) => [$type => __("account.merchant_create.business.{$type}")])->all();
    }
}
