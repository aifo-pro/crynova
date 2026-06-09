<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\Merchant;
use App\Models\StaticWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function project(Merchant $merchant)
    {
        return view('merchant.settings.project', compact('merchant'));
    }

    public function updateProject(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'min:3', 'max:60'],
            'business_type'       => ['required', 'string', 'max:50'],
            'project_description' => ['required', 'string', 'min:100', 'max:250'],
            'logo'                => ['nullable', 'image', 'mimes:png,jpeg,jpg', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('logos', 'public');
        }
        unset($validated['logo']);

        $merchant->update($validated);
        AuditLog::record('merchant.settings_updated', $merchant);

        return back()->with('success', __('merchant_settings.project.saved'));
    }

    public function destroy(Merchant $merchant)
    {
        AuditLog::record('merchant.deleted', $merchant);
        $merchant->delete();

        return redirect()->route('account.dashboard')->with('success', __('merchant_settings.project.deleted'));
    }

    public function integration(Merchant $merchant)
    {
        $cmsList = ['WordPress', 'WooCommerce', 'OpenCart', 'Tilda', 'Bitrix', 'PrestaShop', 'Magento', 'Other CMS'];
        $secret = $merchant->webhook_secret;

        return view('merchant.settings.integration', compact('merchant', 'cmsList', 'secret'));
    }

    public function updateIntegration(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'domain'          => ['nullable', 'string', 'max:255'],
            'cms'             => ['nullable', 'string', 'max:50'],
            'success_url'     => ['nullable', 'url', 'max:255'],
            'fail_url'        => ['nullable', 'url', 'max:255'],
            'callback_url'    => ['nullable', 'url', 'max:255'],
            'postback_format' => ['required', 'in:json,form-data'],
        ]);

        if (! empty($validated['domain'])) {
            $domain = preg_replace('#^https?://#i', '', rtrim($validated['domain'], '/'));
            $validated['domain'] = explode('/', $domain)[0];
            $validated['website'] = 'https://' . $validated['domain'];
        }

        $merchant->update($validated);
        AuditLog::record('merchant.integration_updated', $merchant);

        return back()->with('success', __('merchant_settings.integration.saved'));
    }

    public function regenerateSecret(Merchant $merchant)
    {
        $raw = Str::random(40);
        $merchant->update(['webhook_secret' => $raw]);
        AuditLog::record('merchant.secret_regenerated', $merchant);

        return back()->with('success', __('merchant_settings.integration.secret_regenerated'))->with('new_secret', $raw);
    }

    public function currencies(Merchant $merchant)
    {
        $all = Currency::where('is_active', true)->orderBy('code')->get();
        $enabled = $merchant->currencies()->pluck('currencies.id')->all();
        $grouped = $this->groupCurrencies($all);

        return view('merchant.settings.currencies', compact('merchant', 'grouped', 'enabled'));
    }

    public function updateCurrencies(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'currencies'   => ['array'],
            'currencies.*' => ['integer', 'exists:currencies,id'],
        ]);

        $merchant->currencies()->sync(
            collect($validated['currencies'] ?? [])->mapWithKeys(fn ($id) => [$id => ['is_enabled' => true]])->all()
        );
        AuditLog::record('merchant.currencies_updated', $merchant);

        return back()->with('success', __('merchant_settings.currencies.saved'));
    }

    public function fees(Merchant $merchant)
    {
        return view('merchant.settings.fees', compact('merchant'));
    }

    public function updateFees(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'transfer_fee_payer'    => ['required', 'in:client,merchant'],
            'service_fee_payer'     => ['required', 'in:client,merchant'],
            'partial_confirm_value' => ['nullable', 'numeric', 'gte:0'],
            'partial_confirm_unit'  => ['required', 'in:fixed,percent'],
            'aml_enabled'           => ['nullable', 'boolean'],
        ]);

        $validated['aml_enabled'] = $request->boolean('aml_enabled');
        $validated['partial_confirm_value'] = $validated['partial_confirm_value'] ?? 0;

        $merchant->update($validated);
        AuditLog::record('merchant.fees_updated', $merchant);

        return back()->with('success', __('merchant_settings.fees.saved'));
    }

    public function wallets(Request $request, Merchant $merchant)
    {
        $wallets = $merchant->staticWallets()
            ->with('currency')
            ->when($request->input('search'), fn ($q, $s) =>
                $q->where('address', 'like', "%{$s}%")->orWhere('client_identifier', 'like', "%{$s}%"))
            ->when($request->input('currency'), fn ($q, $c) => $q->where('currency_id', $c))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $currencies = $merchant->currencies()->wherePivot('is_enabled', true)->get();
        if ($currencies->isEmpty()) {
            $currencies = Currency::where('is_active', true)->orderBy('code')->get();
        }

        return view('merchant.settings.wallets', compact('merchant', 'wallets', 'currencies'));
    }

    public function storeWallet(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'currency_id'       => ['required', 'integer', 'exists:currencies,id'],
            'client_identifier' => ['nullable', 'string', 'max:100'],
        ]);

        $currency = Currency::findOrFail($validated['currency_id']);

        try {
            $wallet = app(\App\Services\WalletService::class)->assignDepositWallet($currency);
            $address = $wallet->address;
            $memo = $wallet->memo;
            $hdPath = $wallet->hd_path;
        } catch (\Throwable $e) {
            return back()->with('error', __('merchant_settings.wallets.generate_failed', ['message' => $e->getMessage()]));
        }

        StaticWallet::create([
            'merchant_id'       => $merchant->id,
            'currency_id'       => $currency->id,
            'address'           => $address,
            'memo'              => $memo,
            'hd_path'           => $hdPath,
            'client_identifier' => $validated['client_identifier'] ?? null,
            'status'            => 'active',
        ]);

        AuditLog::record('merchant.static_wallet_created', $merchant);

        return back()->with('success', __('merchant_settings.wallets.created'));
    }

    public function destroyWallet(Merchant $merchant, StaticWallet $wallet)
    {
        abort_unless($wallet->merchant_id === $merchant->id, 403);
        $wallet->delete();

        return back()->with('success', __('merchant_settings.wallets.deleted'));
    }

    public function constructor(Merchant $merchant)
    {
        $config = array_merge([
            'theme'          => 'classic',
            'currency_order' => 'default',
            'language'       => 'auto',
        ], $merchant->checkout_config ?? []);

        return view('merchant.settings.constructor', compact('merchant', 'config'));
    }

    public function updateConstructor(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'theme'          => ['required', 'in:classic,minimal,dark,gradient'],
            'currency_order' => ['required', 'in:default,custom'],
            'language'       => ['required', 'in:auto,en,ru,ua,uk'],
        ]);

        if (($validated['language'] ?? null) === 'ua') {
            $validated['language'] = 'uk';
        }

        $merchant->update(['checkout_config' => $validated]);
        AuditLog::record('merchant.checkout_builder_updated', $merchant);

        return back()->with('success', __('merchant_settings.constructor.saved'));
    }

    public function widget(Merchant $merchant)
    {
        $config = array_merge([
            'type'     => 'button',
            'template' => 'cc-paywith',
            'text'     => 'Pay with crypto',
            'style'    => 'dark',
            'size'     => 'standard',
            'amount'   => '10',
            'currency' => 'USD',
            'language' => 'uk',
        ], $merchant->widget_config ?? []);

        return view('merchant.settings.widget', compact('merchant', 'config'));
    }

    public function updateWidget(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'type'     => ['required', 'in:button,inline'],
            'template' => ['required', 'string', 'max:30'],
            'text'     => ['nullable', 'string', 'max:40'],
            'style'    => ['required', 'in:dark,light'],
            'size'     => ['required', 'in:standard,compact,large'],
            'amount'   => ['nullable', 'numeric', 'gte:0'],
            'currency' => ['required', 'string', 'max:10'],
            'language' => ['required', 'in:en,ru,ua,uk'],
        ]);

        if (($validated['language'] ?? null) === 'ua') {
            $validated['language'] = 'uk';
        }

        $merchant->update(['widget_config' => $validated]);
        AuditLog::record('merchant.widget_builder_updated', $merchant);

        return back()->with('success', __('merchant_settings.widget.saved'));
    }

    public function autoconversion(Merchant $merchant)
    {
        $targets = Currency::where('is_active', true)
            ->where(fn ($q) => $q->where('code', 'like', 'USDT%')
                ->orWhere('code', 'like', 'USDC%')
                ->orWhere('code', 'like', 'DAI%')
                ->orWhere('code', 'like', 'USDD%'))
            ->orderBy('code')->get();

        if ($targets->isEmpty()) {
            $targets = Currency::where('is_active', true)->orderBy('code')->get();
        }

        return view('merchant.settings.autoconversion', compact('merchant', 'targets'));
    }

    public function updateAutoconversion(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'autoconvert_enabled'            => ['nullable', 'boolean'],
            'autoconvert_target_currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
        ]);

        $enabled = $request->boolean('autoconvert_enabled');

        if ($enabled && empty($validated['autoconvert_target_currency_id'])) {
            return back()->with('error', __('merchant_settings.autoconversion.choose_target'));
        }

        $merchant->update([
            'autoconvert_enabled'            => $enabled,
            'autoconvert_target_currency_id' => $enabled ? $validated['autoconvert_target_currency_id'] : null,
        ]);
        AuditLog::record('merchant.autoconvert_updated', $merchant);

        return back()->with('success', __('merchant_settings.autoconversion.saved'));
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
