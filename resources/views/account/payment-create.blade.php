@extends('layouts.app')
@section('title', __('account.payments.create_title'))

@section('content')
@php
    $currencyOptions = $currencies->values();
    $selectedCurrencyId = (string) old('currency_id', optional($currencyOptions->first())->id);
    $selectedCurrency = $currencyOptions->firstWhere('id', (int) $selectedCurrencyId) ?? $currencyOptions->first();
    $selectedProjectId = (string) old('merchant_id', optional($projects->first())->id);
    $selectedProject = $projects->firstWhere('id', (int) $selectedProjectId) ?? $projects->first();
@endphp

<div
    class="space-y-6"
    x-data="{
        amount: @js(old('amount', '')),
        currencyId: @js($selectedCurrencyId),
        currencyCode: @js($selectedCurrency?->code ?? ''),
        projectId: @js($selectedProjectId),
        projectName: @js($selectedProject?->name ?? ''),
        setCurrency(id, code) {
            this.currencyId = String(id);
            this.currencyCode = code;
        }
    }"
>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <x-badge variant="blue">{{ __('account.payments.create_badge') }}</x-badge>
            <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950">{{ __('account.payments.create_title') }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ __('account.payments.create_subtitle') }}</p>
        </div>
        <x-button href="{{ route('account.payments') }}" variant="secondary" icon="arrow-left">
            {{ __('account.payments.back_to_payments') }}
        </x-button>
    </div>

    @if($projects->isEmpty())
        <section class="rounded-[1.75rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                <x-icon name="credit-card" class="h-6 w-6" />
            </span>
            <h2 class="mt-5 text-xl font-black text-slate-950">{{ __('account.payments.no_active_projects') }}</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">{{ __('account.payments.no_active_projects_text') }}</p>
            <x-button href="{{ route('account.projects') }}" class="mt-6" icon="plus">{{ __('account.payments.to_projects') }}</x-button>
        </section>
    @else
        <form method="POST" action="{{ route('account.payments.store') }}" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_23rem]">
            @csrf

            <section class="rounded-[1.75rem] border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">{{ __('account.payments.choose_params') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('account.payments.choose_params_text') }}</p>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-2xl bg-slate-50 px-3 py-2 text-xs font-bold text-slate-500 ring-1 ring-slate-200">
                        <x-icon name="shield-check" class="h-4 w-4 text-blue-600" />
                        {{ __('account.payments.secure_checkout') }}
                    </span>
                </div>

                <div class="space-y-6 p-5">
                    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,0.7fr)]">
                        <div>
                            <label class="fin-label" for="amount">{{ __('account.payments.amount_to_pay') }}</label>
                            <div class="relative">
                                <input id="amount" name="amount" type="number" step="any" min="0.00000001" x-model="amount" class="fin-input min-h-14 pr-24 text-2xl font-black tracking-tight @error('amount') border-rose-500 @enderror" placeholder="0.00" required>
                                <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 rounded-xl bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500" x-text="currencyCode || '—'"></span>
                            </div>
                            @error('amount')<p class="mt-2 text-xs font-medium text-rose-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="fin-label" for="merchant_id">{{ __('account.payments.choose_project') }}</label>
                            <select
                                id="merchant_id"
                                name="merchant_id"
                                class="fin-input min-h-14 @error('merchant_id') border-rose-500 @enderror"
                                x-model="projectId"
                                @change="projectName = $event.target.selectedOptions[0]?.dataset.name || ''"
                                required
                            >
                                <option value="">{{ __('account.payments.select_project') }}</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" data-name="{{ $project->name }}" @selected((string) $project->id === $selectedProjectId)>{{ $project->name }}</option>
                                @endforeach
                            </select>
                            @error('merchant_id')<p class="mt-2 text-xs font-medium text-rose-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                            <label class="fin-label mb-0">{{ __('account.balance.currency') }}</label>
                            <span class="text-xs font-semibold text-slate-400">{{ __('account.payments.currency_hint') }}</span>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach($currencyOptions as $currency)
                                @php($network = strtoupper((string) $currency->network))
                                <label class="group cursor-pointer" @click="setCurrency(@js((string) $currency->id), @js($currency->code))">
                                    <input
                                        type="radio"
                                        name="currency_id"
                                        value="{{ $currency->id }}"
                                        class="sr-only"
                                        required
                                        @checked((string) $currency->id === $selectedCurrencyId)
                                    >
                                    <span
                                        :class="currencyId === @js((string) $currency->id) ? 'border-blue-500 bg-blue-50 shadow-lg shadow-blue-600/10 ring-4 ring-blue-100' : 'border-slate-200 bg-white hover:border-blue-200 hover:bg-slate-50'"
                                        class="flex min-h-[5.5rem] items-center gap-3 rounded-2xl border p-4 transition"
                                    >
                                        <x-coin-icon :code="$currency->code" class="h-11 w-11" />
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-black text-slate-950">{{ $currency->code }}</span>
                                            <span class="mt-1 block truncate text-xs font-medium text-slate-500">{{ $currency->name }}</span>
                                            <span class="mt-2 inline-flex rounded-full bg-white px-2.5 py-1 text-[0.68rem] font-bold uppercase tracking-[0.08em] text-slate-500 ring-1 ring-slate-200">{{ $network }}</span>
                                        </span>
                                        <span
                                            :class="currencyId === @js((string) $currency->id) ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-300'"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full transition"
                                        >
                                            <x-icon name="check" class="h-4 w-4" />
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('currency_id')<p class="mt-2 text-xs font-medium text-rose-500">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <aside class="space-y-4">
                <section class="rounded-[1.75rem] border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-lg font-black text-slate-950">{{ __('account.payments.invoice_preview') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('account.payments.review_subtitle') }}</p>
                    </div>

                    <div class="space-y-4 p-5">
                        <div class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 via-white to-cyan-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-600">INV-NEW</p>
                            <p class="mt-3 text-3xl font-black tracking-tight text-slate-950">
                                <span x-text="amount || '0'"></span>
                                <span class="text-blue-600" x-text="currencyCode"></span>
                            </p>
                            <p class="mt-2 truncate text-sm font-semibold text-slate-500" x-text="projectName || @js(__('account.payments.select_project'))"></p>
                        </div>

                        <div class="grid gap-3">
                            <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                    <x-icon name="qr" class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="text-sm font-black text-slate-950">{{ __('account.payments.hosted_checkout') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ __('account.payments.hosted_checkout_text') }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                    <x-icon name="bell" class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="text-sm font-black text-slate-950">{{ __('account.payments.live_status') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ __('account.payments.live_status_text') }}</p>
                                </div>
                            </div>
                        </div>

                        <x-button type="submit" icon="credit-card" class="w-full rounded-2xl">
                            {{ __('account.payments.create_invoice_button') }}
                        </x-button>
                    </div>
                </section>

                <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-black text-slate-950">{{ __('account.payments.available_assets') }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ __('account.payments.available_assets_text') }}</p>
                        </div>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                            <x-icon name="coins" class="h-5 w-5" />
                        </span>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($currencyOptions->take(8) as $currency)
                            <span class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700">
                                <x-coin-icon :code="$currency->code" class="h-6 w-6" />
                                {{ $currency->code }}
                            </span>
                        @endforeach
                    </div>
                </section>
            </aside>
        </form>
    @endif
</div>
@endsection
