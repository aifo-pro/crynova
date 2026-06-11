<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\Merchant;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MerchantController extends Controller
{
    public function create()
    {
        $currencies = Currency::where('is_active', true)->orderBy('code')->get();
        $grouped = $this->groupCurrencies($currencies);

        $businessTypes = collect([
            'ecommerce',
            'online_school',
            'service',
            'digital_goods',
            'telegram_bot',
            'saas',
            'donations',
            'other',
        ])->mapWithKeys(fn ($type) => [$type => __("account.merchant_create.business.{$type}")])->all();

        $cmsList = ['WordPress', 'WooCommerce', 'OpenCart', 'Tilda', 'Bitrix', 'PrestaShop', 'Magento', 'Custom / Other'];

        return view('account.merchants.create', compact('grouped', 'businessTypes', 'cmsList'));
    }

    public function store(Request $request, TelegramNotificationService $telegram)
    {
        $validated = $request->validate([
            'accept_type'         => ['required', 'in:website,donation'],
            'name'                => ['required', 'string', 'min:3', 'max:60'],
            'business_type'       => ['required', 'string', 'max:50'],
            'project_description' => ['required', 'string', 'min:100', 'max:250'],
            'currencies'          => ['required', 'array', 'min:1'],
            'currencies.*'        => ['integer', 'exists:currencies,id'],
            'base_currency_code'  => ['nullable', 'string', 'max:10'],
            'domain'              => ['nullable', 'string', 'max:255'],
            'cms'                 => ['nullable', 'string', 'max:50'],
            'success_url'         => ['nullable', 'url', 'max:255'],
            'fail_url'            => ['nullable', 'url', 'max:255'],
            'callback_url'        => ['nullable', 'url', 'max:255'],
        ]);

        $user = $request->user();

        $domain = null;
        if (! empty($validated['domain'])) {
            $domain = preg_replace('#^https?://#i', '', rtrim((string) $validated['domain'], '/'));
            $domain = explode('/', $domain)[0];
        }

        $isDonation = $validated['accept_type'] === Merchant::ACCEPT_DONATION;

        $merchant = DB::transaction(function () use ($user, $validated, $domain, $isDonation) {
            $merchant = Merchant::create([
                'user_id'             => $user->id,
                'name'                => $validated['name'],
                'slug'                => Str::slug($validated['name']) . '-' . Str::lower(Str::random(6)),
                'accept_type'         => $validated['accept_type'],
                'merchant_type'       => 'domain',
                'domain'              => $domain,
                'website'             => $domain ? 'https://' . $domain : null,
                'business_type'       => $validated['business_type'],
                'project_description' => $validated['project_description'],
                'base_currency_code'  => $validated['base_currency_code'] ?? 'USD',
                'cms'                 => $validated['cms'] ?? null,
                'success_url'         => $validated['success_url'] ?? null,
                'fail_url'            => $validated['fail_url'] ?? null,
                'callback_url'        => $validated['callback_url'] ?? null,
                'status'              => $isDonation ? Merchant::STATUS_MODERATION : Merchant::STATUS_UNVERIFIED,
                'verified_at'         => $isDonation ? now() : null,
                'is_active'           => false,
                'fee_percent'         => (float) \App\Models\Setting::get('default_fee_percent', 1.00),
            ]);

            $merchant->currencies()->sync(
                collect($validated['currencies'])->mapWithKeys(fn ($id) => [$id => ['is_enabled' => true]])->all()
            );

            ['raw_key' => $rawKey] = \App\Models\ApiKey::generate(
                $merchant,
                'Primary key',
                ['currencies.read', 'invoices.create', 'invoices.read', 'invoices.cancel']
            );
            $merchant->api_key = $rawKey;
            $merchant->save();

            if (! $isDonation) {
                $merchant->ensureVerificationCode();
            }

            return $merchant;
        });

        AuditLog::record('merchant.created', $merchant);
        $telegram->notifyMerchantCreated($merchant);

        return $isDonation
            ? redirect()->route('merchant.control', $merchant)
                ->with('success', __('account.merchant_create.created_moderation'))
            : redirect()->route('merchant.verification', $merchant)
                ->with('success', __('account.merchant_create.created_verify'));
    }

    private function groupCurrencies($currencies): array
    {
        $stableCodes = ['USDT', 'USDC', 'USDD', 'DAI', 'PYUSD', 'XAUT'];

        $groups = [
            'stable'     => ['label' => __('account.merchant_create.groups.stable'), 'icon' => 'coins', 'items' => []],
            'blockchain' => ['label' => __('account.merchant_create.groups.blockchain'), 'icon' => 'layers', 'items' => []],
            'other'      => ['label' => __('account.merchant_create.groups.other'), 'icon' => 'sparkles', 'items' => []],
        ];

        foreach ($currencies as $c) {
            $base = strtoupper(preg_replace('/[^A-Z]/i', '', explode('_', $c->code)[0]));
            if (in_array($base, $stableCodes, true)) {
                $groups['stable']['items'][] = $c;
            } elseif (in_array($base, ['BTC', 'ETH', 'LTC', 'TRX', 'SOL', 'TON', 'BNB', 'DOGE'], true)) {
                $groups['blockchain']['items'][] = $c;
            } else {
                $groups['other']['items'][] = $c;
            }
        }

        return array_filter($groups, fn ($g) => ! empty($g['items']));
    }
}
