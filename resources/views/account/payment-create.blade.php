@extends('layouts.app')
@section('title', __('account.payments.create_title'))

@section('content')
<div class="space-y-6" x-data="{ amount: '{{ old('amount','') }}', currency: 'USDT' }">
    <div class="flex items-center justify-between">
        <h1 class="flex items-center gap-2 text-2xl font-semibold text-slate-950"><x-icon name="credit-card" class="h-6 w-6 text-blue-600" /> {{ __('account.payments.create_title') }}</h1>
        <a href="{{ route('account.payments') }}" class="text-sm font-semibold text-blue-600 hover:underline">← {{ __('account.payments.back') }}</a>
    </div>
    @if($projects->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
            <p class="text-lg font-semibold text-slate-950">{{ __('account.payments.no_active_projects') }}</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ __('account.payments.no_active_projects_text') }}</p>
            <x-button href="{{ route('account.projects') }}" class="mt-5">{{ __('account.payments.to_projects') }}</x-button>
        </div>
    @else
        <form method="POST" action="{{ route('account.payments.store') }}" class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            @csrf
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-5 font-semibold text-slate-950">{{ __('account.payments.choose_params') }}</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="fin-label">{{ __('account.payments.amount_to_pay') }}</label>
                        <input name="amount" type="number" step="any" min="0" x-model="amount" class="fin-input" placeholder="0.00" required>
                    </div>
                    <div>
                        <label class="fin-label">{{ __('account.balance.currency') }}</label>
                        <select name="currency_id" x-model="currency" class="fin-input" required>
                            @foreach($currencies as $currency)<option value="{{ $currency->id }}">{{ $currency->code }}</option>@endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="fin-label">{{ __('account.payments.choose_project') }}</label>
                        <select name="merchant_id" class="fin-input" required>
                            <option value="">{{ __('account.payments.select_project') }}</option>
                            @foreach($projects as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <span class="font-semibold text-slate-950">INV-NEW</span>
                    <x-icon name="wallet" class="h-5 w-5 text-slate-400" />
                </div>
                <div class="flex items-center justify-between py-4">
                    <span class="text-sm text-slate-500">{{ __('account.payments.total') }}</span>
                    <span class="text-lg font-bold text-slate-950" x-text="'$ ' + (amount || '0')"></span>
                </div>
                <x-button type="submit" icon="credit-card" class="w-full rounded-full">{{ __('account.payments.create_link') }}</x-button>
            </div>
        </form>
    @endif
</div>
@endsection
