@extends('layouts.app')
@section('title', __('account.payments.title'))

@section('content')
@php
    $columnLabels = [
        'type' => __('account.balance.type'),
        'status' => __('account.payments.status'),
        'currency' => __('account.balance.currency'),
        'amount' => __('account.payments.invoice_amount'),
        'received' => __('account.payments.received'),
        'project' => __('account.balance.project'),
        'date' => __('account.balance.date'),
    ];
@endphp
<div class="space-y-6" x-data="{ settings: false, cols: JSON.parse(localStorage.getItem('paycols') || '{}'), show(k){ return this.cols[k] !== false; }, toggle(k){ this.cols[k] = this.show(k) ? false : true; localStorage.setItem('paycols', JSON.stringify(this.cols)); } }">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-slate-950">{{ __('account.payments.title') }} <span class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-xs text-slate-400">?</span></h1>
    </div>
    <div class="flex flex-col items-start gap-3 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <p class="max-w-2xl text-sm text-slate-500">{{ __('account.payments.create_text') }}</p>
        <x-button href="{{ route('account.payments.create') }}" icon="plus" class="shrink-0 rounded-full">{{ __('account.payments.create_link') }}</x-button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-400">{{ __('account.payments.created_sum') }}</p><p class="mt-2 text-2xl font-bold text-slate-950">$ {{ number_format($stats['createdSum'], 2) }}</p></div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-400">{{ __('account.payments.paid_sum') }}</p><p class="mt-2 text-2xl font-bold text-emerald-600">$ {{ number_format($stats['paidSum'], 2) }}</p></div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-400">{{ __('account.payments.partial_sum') }}</p><p class="mt-2 text-2xl font-bold text-amber-600">$ {{ number_format($stats['partialSum'], 2) }}</p></div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-400">{{ __('account.payments.conversion') }}</p><p class="mt-2 text-2xl font-bold text-blue-600">{{ $stats['conversion'] }} %</p></div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2"><x-icon name="layers" class="h-5 w-5 text-blue-600" /><h2 class="font-semibold text-slate-950">{{ __('account.balance.history') }}</h2></div>
            <button type="button" @click="settings=true" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"><x-icon name="settings" class="h-4 w-4" /> {{ __('account.payments.settings') }}</button>
        </div>

        <form method="GET" class="mb-4 flex flex-wrap gap-3">
            <input name="search" value="{{ request('search') }}" class="fin-input min-w-40 flex-1" placeholder="{{ __('account.balance.search') }}">
            <div class="w-44"><x-project-select name="project" :projects="$projects" :selected="request('project')" :placeholder="__('account.balance.project')" /></div>
            <select name="currency" class="fin-input w-36"><option value="">{{ __('account.balance.currency') }}</option>@foreach($currencies as $currency)<option value="{{ $currency->id }}" @selected(request('currency')==$currency->id)>{{ $currency->code }}</option>@endforeach</select>
            <select name="status" class="fin-input w-40"><option value="">{{ __('account.payments.status') }}</option>@foreach(['pending','waiting_confirmations','paid','underpaid','overpaid','expired','failed','refunded'] as $status)<option value="{{ $status }}" @selected(request('status')==$status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select>
            <x-button type="submit" variant="secondary">{{ __('account.balance.filter') }}</x-button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                    @foreach($columnLabels as $key => $label)<th class="px-3 py-3" x-show="show('{{ $key }}')">{{ $label }}</th>@endforeach
                </tr></thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr class="border-b border-slate-50 hover:bg-slate-50/60">
                            <td class="px-3 py-3 text-slate-600" x-show="show('type')">{{ __('account.payments.type_income') }}</td>
                            <td class="px-3 py-3" x-show="show('status')"><x-status-badge :status="$invoice->status" /></td>
                            <td class="px-3 py-3 font-semibold text-slate-950" x-show="show('currency')">{{ optional($invoice->currency)->code ?? $invoice->price_currency ?? '—' }}</td>
                            <td class="px-3 py-3 font-mono" x-show="show('amount')">{{ $invoice->amount ?? $invoice->price_amount }}</td>
                            <td class="px-3 py-3 font-mono text-slate-500" x-show="show('received')">{{ $invoice->amount_received ?? '0' }}</td>
                            <td class="px-3 py-3 text-slate-600" x-show="show('project')">{{ $invoice->merchant?->name ?? '-' }}</td>
                            <td class="px-3 py-3 text-xs text-slate-400" x-show="show('date')">{{ $invoice->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-12 text-center text-slate-400">{{ __('account.balance.no_transactions') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $invoices->links() }}</div>
    </div>

    <div x-show="settings" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="settings=false">
        <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="flex items-center gap-2 text-lg font-semibold text-slate-950"><x-icon name="settings" class="h-5 w-5" /> {{ __('account.payments.settings') }}</h3>
                <button type="button" @click="settings=false" class="text-slate-400 hover:text-slate-900"><x-icon name="x" class="h-5 w-5" /></button>
            </div>
            <div class="space-y-1">
                @foreach($columnLabels as $key => $label)
                    <label class="flex items-center justify-between rounded-xl px-3 py-2.5 hover:bg-slate-50">
                        <span class="text-sm text-slate-700">{{ $label }}</span>
                        <input type="checkbox" :checked="show('{{ $key }}')" @change="toggle('{{ $key }}')" class="rounded border-slate-300 text-blue-600">
                    </label>
                @endforeach
            </div>
            <div class="mt-4 flex justify-end"><button type="button" @click="settings=false" class="rounded-full bg-blue-600 px-6 py-2 text-sm font-semibold text-white">{{ __('account.payments.done') }}</button></div>
        </div>
    </div>
</div>
@endsection
