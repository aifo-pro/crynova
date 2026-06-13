@extends('layouts.app')
@section('title', __('account.balance.title'))

@section('content')
@php
    $tabs = [
        'assets' => __('account.balance.assets'),
        'withdraw' => __('account.balance.withdraw'),
        'mass' => __('account.balance.mass'),
        'addresses' => __('account.balance.addresses'),
        'autowd' => __('account.balance.autowd'),
    ];
    $formatBalanceAmount = function ($value): string {
        $number = (float) $value;
        $decimals = abs($number) >= 1 ? 2 : 8;
        $formatted = number_format($number, $decimals, '.', '');

        return $decimals > 2 ? (rtrim(rtrim($formatted, '0'), '.') ?: '0') : $formatted;
    };
@endphp
<div class="space-y-6" x-data="{ tab: 'assets', hideZero: false }">
    <h1 class="text-2xl font-semibold text-slate-950">{{ __('account.balance.title') }} <x-help-tip :text="__('account.balance.help')" /></h1>

    <div class="rounded-3xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
        <p class="px-2 pb-3 text-sm text-slate-400">{{ __('account.section_select') }}</p>
        <nav class="flex flex-wrap gap-1">
            @foreach($tabs as $key => $label)
                <button type="button" @click="tab='{{ $key }}'" :class="tab==='{{ $key }}' ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'" class="shrink-0 rounded-xl px-4 py-2 text-sm font-medium transition">{{ $label }}</button>
            @endforeach
        </nav>
    </div>

    <div x-show="tab==='assets'">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2"><x-icon name="wallet" class="h-5 w-5 text-blue-600" /><h2 class="font-semibold text-slate-950">{{ __('account.balance.assets') }}</h2></div>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-500">
                    <input type="checkbox" x-model="hideZero" class="sr-only peer">
                    <span role="switch" :class="hideZero ? 'bg-blue-600' : 'bg-slate-200'" class="relative inline-flex h-5 w-9 items-center rounded-full transition"><span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition" :class="hideZero ? 'translate-x-4' : 'translate-x-1'"></span></span>
                    {{ __('account.balance.hide_zero') }}
                </label>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($currencies as $currency)
                    @php $zero = bccomp((string)$currency->bal_available, '0', 18) <= 0 && bccomp((string)$currency->bal_locked, '0', 18) <= 0; @endphp
                    <div class="rounded-2xl border border-slate-200 px-4 py-3" x-show="!hideZero || {{ $zero ? 'false' : 'true' }}">
                        <div class="flex items-center gap-2">
                            <x-coin-icon :code="$currency->code" class="h-8 w-8" />
                            <div class="min-w-0">
                                <p class="break-words text-sm font-semibold leading-5 text-slate-950">{{ $formatBalanceAmount($currency->bal_available) }} {{ $currency->code }}</p>
                                <p class="break-words text-xs leading-4 text-slate-400">{{ $currency->name }} @if(str_contains($currency->code,'_'))· {{ \Illuminate\Support\Str::after($currency->code,'_') }}@endif</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2"><x-icon name="layers" class="h-5 w-5 text-blue-600" /><h2 class="font-semibold text-slate-950">{{ __('account.balance.history') }}</h2></div>
            <form method="GET" class="mb-4 flex flex-wrap gap-3">
                <input name="search" value="{{ request('search') }}" class="fin-input min-w-40 flex-1" placeholder="{{ __('account.balance.search') }}">
                <select name="currency" class="fin-input w-36"><option value="">{{ __('account.balance.currency') }}</option>@foreach($currencies as $currency)<option value="{{ $currency->id }}" @selected(request('currency')==$currency->id)>{{ $currency->code }}</option>@endforeach</select>
                <select name="type" class="fin-input w-36">
                    <option value="">{{ __('account.balance.type') }}</option>
                    @foreach(['credit' => __('account.balance.credit'), 'debit' => __('account.balance.debit'), 'hold' => __('account.balance.hold'), 'fee' => __('account.balance.fee'), 'refund' => __('account.balance.refund')] as $value => $label)
                        <option value="{{ $value }}" @selected(request('type')==$value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-button type="submit" variant="secondary">{{ __('account.balance.filter') }}</x-button>
            </form>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-3 py-3">{{ __('account.balance.type') }}</th><th class="px-3 py-3">{{ __('account.balance.project') }}</th><th class="px-3 py-3">{{ __('account.balance.currency') }}</th><th class="px-3 py-3">{{ __('account.balance.amount') }}</th><th class="px-3 py-3">{{ __('account.balance.note') }}</th><th class="px-3 py-3">{{ __('account.balance.date') }}</th>
                    </tr></thead>
                    <tbody>
                        @forelse($movements as $movement)
                            <tr class="border-b border-slate-50">
                                <td class="px-3 py-3"><span class="text-xs font-semibold {{ $movement->type==='credit' ? 'text-emerald-600' : ($movement->type==='hold' ? 'text-amber-600' : 'text-rose-500') }}">{{ strtoupper($movement->type) }}</span></td>
                                <td class="px-3 py-3 text-slate-600">{{ $movement->merchant?->name ?? '-' }}</td>
                                <td class="px-3 py-3 font-semibold text-slate-950">{{ $movement->currency->code }}</td>
                                <td class="px-3 py-3 font-mono">{{ $movement->amount }}</td>
                                <td class="px-3 py-3 text-xs text-slate-400">{{ \Illuminate\Support\Str::limit($movement->note, 40) }}</td>
                                <td class="px-3 py-3 text-xs text-slate-400">{{ $movement->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-12 text-center text-slate-400">{{ __('account.balance.no_transactions') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $movements->links() }}</div>
        </div>
    </div>
    @php $noProjects = $projects->isEmpty(); @endphp

    {{-- ── Виведення коштів ─────────────────────────────────────────── --}}
    <div x-show="tab==='withdraw'" x-cloak class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-950">{{ __('account.balance.f_wd_title') }}</h2>
            @if($noProjects)
                <p class="text-sm text-slate-500">{{ __('account.balance.f_no_proj_wd') }}</p>
            @else
            <form method="POST" action="{{ route('account.balance.withdraw') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf
                <div><label class="fin-label">{{ __('account.balance.f_project') }}</label><x-project-select name="merchant_id" :projects="$projects" required /></div>
                <div><label class="fin-label">{{ __('account.balance.f_currency') }}</label><x-currency-select name="currency_id" :currencies="$allCurrencies" required /></div>
                <div><label class="fin-label">{{ __('account.balance.f_amount') }}</label><input name="amount" type="number" step="any" min="0" required class="fin-input" placeholder="0.00"></div>
                <div><label class="fin-label">{{ __('account.balance.f_to_address') }}</label><input name="to_address" type="text" required class="fin-input" placeholder="{{ __('account.balance.f_wallet_ph') }}"></div>
                <div class="sm:col-span-2"><label class="fin-label">{{ __('account.balance.f_memo') }} <span class="text-slate-400">{{ __('account.balance.f_memo_opt') }}</span></label><input name="memo" type="text" class="fin-input"></div>
                <div class="sm:col-span-2"><x-button type="submit" icon="banknote">{{ __('account.balance.f_create_req') }}</x-button></div>
            </form>
            @endif
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-950">{{ __('account.balance.f_history') }}</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-3 py-3">{{ __('account.balance.f_project') }}</th><th class="px-3 py-3">{{ __('account.balance.f_currency') }}</th><th class="px-3 py-3">{{ __('account.balance.f_amount') }}</th><th class="px-3 py-3">{{ __('account.balance.f_th_address') }}</th><th class="px-3 py-3">{{ __('account.balance.type') }}</th><th class="px-3 py-3">{{ __('account.balance.date') }}</th>
                    </tr></thead>
                    <tbody>
                        @forelse($withdrawals as $w)
                        <tr class="border-b border-slate-50">
                            <td class="px-3 py-3 text-slate-600">{{ $w->merchant?->name ?? '—' }}</td>
                            <td class="px-3 py-3 font-semibold text-slate-950">{{ $w->currency->code }}</td>
                            <td class="px-3 py-3 font-mono">{{ $w->amount }}</td>
                            <td class="px-3 py-3 font-mono text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($w->to_address, 16) }}</td>
                            <td class="px-3 py-3"><x-status-badge :status="$w->status" /></td>
                            <td class="px-3 py-3 text-xs text-slate-400">{{ $w->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-3 py-10 text-center text-slate-400">{{ __('account.balance.f_no_wd') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Масові виплати ──────────────────────────────────────── --}}
    <div x-show="tab==='mass'" x-cloak>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-2 font-semibold text-slate-950">{{ __('account.balance.f_mass_title') }}</h2>
            <p class="mb-4 text-sm text-slate-500">{!! __('account.balance.f_mass_hint') !!}</p>
            @if($noProjects)
                <p class="text-sm text-slate-500">{{ __('account.balance.f_no_proj') }}</p>
            @else
            <form method="POST" action="{{ route('account.balance.mass') }}" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="fin-label">{{ __('account.balance.f_project') }}</label><x-project-select name="merchant_id" :projects="$projects" required /></div>
                    <div><label class="fin-label">{{ __('account.balance.f_currency') }}</label><x-currency-select name="currency_id" :currencies="$allCurrencies" required /></div>
                </div>
                <div><label class="fin-label">{{ __('account.balance.f_payouts_list') }}</label><textarea name="rows" rows="6" required class="fin-input font-mono text-xs" placeholder="TXabc...,10.5,замовлення-1&#10;TXdef...,25,замовлення-2"></textarea></div>
                <x-button type="submit" icon="banknote">{{ __('account.balance.f_create_payouts') }}</x-button>
            </form>
            @endif
        </div>
    </div>

    {{-- ── Збережені адреси ────────────────────────────────────── --}}
    <div x-show="tab==='addresses'" x-cloak class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-950">{{ __('account.balance.f_add_address') }}</h2>
            <form method="POST" action="{{ route('account.balance.addresses.store') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf
                <div><label class="fin-label">{{ __('account.balance.f_name') }}</label><input name="label" type="text" required class="fin-input" placeholder="{{ __('account.balance.f_name_ph') }}"></div>
                <div><label class="fin-label">{{ __('account.balance.f_currency') }}</label><x-currency-select name="currency_id" :currencies="$allCurrencies" required /></div>
                <div><label class="fin-label">{{ __('account.balance.f_address') }}</label><input name="address" type="text" required class="fin-input"></div>
                <div><label class="fin-label">{{ __('account.balance.f_memo') }} <span class="text-slate-400">{{ __('account.balance.f_memo_opt2') }}</span></label><input name="memo" type="text" class="fin-input"></div>
                <div class="sm:col-span-2"><x-button type="submit" icon="plus">{{ __('account.balance.f_save_address') }}</x-button></div>
            </form>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-950">{{ __('account.balance.f_saved_addresses') }}</h2>
            <div class="space-y-2">
                @forelse($addresses as $a)
                <div class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-950">{{ $a->label }} <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500">{{ $a->currency->code }}</span></p>
                        <p class="truncate font-mono text-xs text-slate-400">{{ $a->address }}</p>
                    </div>
                    <form method="POST" action="{{ route('account.balance.addresses.destroy', $a) }}" onsubmit="return confirm('{{ __('account.balance.f_del_addr') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-rose-500 hover:underline">{{ __('account.balance.f_delete') }}</button>
                    </form>
                </div>
                @empty
                <p class="py-6 text-center text-sm text-slate-400">{{ __('account.balance.f_no_addresses') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── Налаштування автовиведення ──────────────────────────────────── --}}
    <div x-show="tab==='autowd'" x-cloak class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-2 font-semibold text-slate-950">{{ __('account.balance.f_rule_title') }}</h2>
            <p class="mb-4 text-sm text-slate-500">{{ __('account.balance.f_rule_text') }}</p>
            @if($noProjects)
                <p class="text-sm text-slate-500">{{ __('account.balance.f_no_proj') }}</p>
            @else
            <form method="POST" action="{{ route('account.balance.auto.store') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf
                <div><label class="fin-label">{{ __('account.balance.f_project') }}</label><x-project-select name="merchant_id" :projects="$projects" required /></div>
                <div><label class="fin-label">{{ __('account.balance.f_currency') }}</label><x-currency-select name="currency_id" :currencies="$allCurrencies" required /></div>
                <div><label class="fin-label">{{ __('account.balance.f_wd_address') }}</label><input name="address" type="text" required class="fin-input"></div>
                <div><label class="fin-label">{{ __('account.balance.f_threshold') }}</label><input name="min_amount" type="number" step="any" min="0" required class="fin-input" placeholder="100"></div>
                <div class="sm:col-span-2 flex items-center gap-2">
                    <input type="hidden" name="is_enabled" value="0">
                    <input type="checkbox" name="is_enabled" value="1" checked class="rounded border-slate-300 text-blue-600">
                    <span class="text-sm text-slate-700">{{ __('account.balance.f_enable_rule') }}</span>
                </div>
                <div class="sm:col-span-2"><x-button type="submit" icon="save">{{ __('account.balance.f_save_rule') }}</x-button></div>
            </form>
            @endif
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-950">{{ __('account.balance.f_active_rules') }}</h2>
            <div class="space-y-2">
                @forelse($autoRules as $r)
                <div class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-950">{{ $r->merchant?->name }} · {{ $r->currency->code }}
                            <span class="ml-1 rounded px-1.5 py-0.5 text-[10px] font-semibold {{ $r->is_enabled ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' }}">{{ $r->is_enabled ? __('account.balance.f_on') : __('account.balance.f_off') }}</span>
                        </p>
                        <p class="truncate font-mono text-xs text-slate-400">≥ {{ $r->min_amount }} → {{ \Illuminate\Support\Str::limit($r->address, 24) }}</p>
                    </div>
                    <form method="POST" action="{{ route('account.balance.auto.destroy', $r) }}" onsubmit="return confirm('{{ __('account.balance.f_del_rule') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-rose-500 hover:underline">{{ __('account.balance.f_delete') }}</button>
                    </form>
                </div>
                @empty
                <p class="py-6 text-center text-sm text-slate-400">{{ __('account.balance.f_no_rules') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
