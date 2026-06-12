<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index()
    {
        $currencies = Currency::orderBy('code')->get();

        return view('admin.currencies.index', compact('currencies'));
    }

    public function edit(Currency $currency)
    {
        return view('admin.currencies.edit', compact('currency'));
    }

    public function update(Request $request, Currency $currency)
    {
        $validated = $request->validate([
            'name'                   => ['required', 'string', 'max:100'],
            'decimals'               => ['required', 'integer', 'min:0', 'max:18'],
            'confirmations_required' => ['required', 'integer', 'min:1'],
            'min_amount'             => ['nullable', 'numeric', 'gte:0'],
            'max_amount'             => ['nullable', 'numeric', 'gte:0'],
            'estimated_fee'          => ['nullable', 'numeric', 'gte:0'],
            'is_active'              => ['boolean'],
        ]);

        $old = $currency->toArray();
        $currency->update($validated);
        AuditLog::record('currency.updated', $currency, $old, $currency->fresh()->toArray());

        return back()->with('success', __('flash.currency_updated'));
    }

    public function toggleActive(Currency $currency)
    {
        $currency->update(['is_active' => ! $currency->is_active]);
        AuditLog::record(
            $currency->is_active ? 'currency.activated' : 'currency.deactivated',
            $currency
        );

        return back()->with('success', "Currency {$currency->code} " . ($currency->is_active ? 'activated' : 'deactivated') . '.');
    }
}
