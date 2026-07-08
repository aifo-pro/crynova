@extends('layouts.app')
@section('title', 'Рахунки — Адмін')

@section('content')
@php
    $statusLabels = [
        'pending' => 'Очікує оплату',
        'waiting_confirmations' => 'Підтвердження',
        'paid' => 'Оплачено',
        'underpaid' => 'Недоплата',
        'overpaid' => 'Переплата',
        'expired' => 'Прострочено',
        'failed' => 'Помилка',
        'refunded' => 'Повернено',
        'cancelled' => 'Скасовано',
    ];

    $statusClasses = [
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'waiting_confirmations' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'underpaid' => 'bg-orange-50 text-orange-700 ring-orange-200',
        'overpaid' => 'bg-cyan-50 text-cyan-700 ring-cyan-200',
        'expired' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'failed' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'refunded' => 'bg-violet-50 text-violet-700 ring-violet-200',
        'cancelled' => 'bg-slate-100 text-slate-700 ring-slate-200',
    ];

    $formatAmount = function ($value): string {
        $value = (string) ($value ?? '0');
        $value = str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;

        return $value === '' ? '0' : $value;
    };
@endphp

<div class="mx-auto w-full max-w-7xl space-y-8">
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                <x-icon name="file-text" class="h-3.5 w-3.5" />
                Адмін-панель
            </div>
            <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Рахунки</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                Глобальний моніторинг рахунків, статусів оплат, валют і webhook-подій по всіх мерчантах.
            </p>
        </div>

        <a href="{{ route('admin.wallets.index') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-blue-200 hover:text-blue-700">
            <x-icon name="wallet" class="h-4 w-4" />
            Реєстр гаманців
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Усього рахунків', 'value' => $stats['total'] ?? 0, 'icon' => 'file-text', 'tone' => 'bg-blue-50 text-blue-700'],
            ['label' => 'Оплачено', 'value' => $stats['paid'] ?? 0, 'icon' => 'check', 'tone' => 'bg-emerald-50 text-emerald-700'],
            ['label' => 'Очікують', 'value' => $stats['pending'] ?? 0, 'icon' => 'clock', 'tone' => 'bg-amber-50 text-amber-700'],
            ['label' => 'Отримано', 'value' => $formatAmount($stats['volume'] ?? 0), 'icon' => 'banknote', 'tone' => 'bg-cyan-50 text-cyan-700'],
        ] as $metric)
            <div class="rounded-[1.4rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-black uppercase leading-5 tracking-[0.16em] text-slate-400">{{ $metric['label'] }}</p>
                        <p class="mt-4 break-words text-3xl font-black tracking-tight text-slate-950">{{ $metric['value'] }}</p>
                    </div>
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl {{ $metric['tone'] }}">
                        <x-icon name="{{ $metric['icon'] }}" class="h-5 w-5" />
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <form method="GET" class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_180px_auto]">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input name="search" value="{{ request('search') }}" class="fin-input min-h-14 rounded-2xl pl-11" placeholder="Пошук UUID, order ID, адреси або мерчанта...">
            </div>
            <select name="status" class="fin-input min-h-14 rounded-2xl">
                <option value="">Усі статуси</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <select name="currency" class="fin-input min-h-14 rounded-2xl">
                <option value="">Усі валюти</option>
                @foreach($currencies as $currency)
                    <option value="{{ $currency->code }}" @selected(request('currency') === $currency->code)>{{ $currency->code }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex min-h-14 flex-1 items-center justify-center rounded-2xl bg-blue-600 px-6 text-sm font-black text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700 lg:flex-none">
                    Фільтр
                </button>
                @if(request()->hasAny(['search', 'status', 'currency']))
                    <a href="{{ route('admin.invoices.index') }}" class="inline-flex min-h-14 items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 text-sm font-bold text-slate-600 transition hover:border-blue-200 hover:text-blue-700">
                        Очистити
                    </a>
                @endif
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/80 px-6 py-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-black tracking-tight text-slate-950">Список рахунків</h2>
                <p class="mt-1 text-sm text-slate-500">Показано {{ $invoices->count() }} із {{ $invoices->total() }} записів.</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-blue-700">
                <span class="h-1.5 w-1.5 rounded-full bg-blue-600"></span>
                Invoice review
            </span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full min-w-[820px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 bg-white text-xs font-black uppercase tracking-[0.12em] text-slate-400">
                        <th class="px-4 py-4">Рахунок</th>
                        <th class="px-4 py-4">Мерчант</th>
                        <th class="px-4 py-4">Валюта</th>
                        <th class="px-4 py-4">Сума</th>
                        <th class="px-4 py-4">Отримано</th>
                        <th class="px-4 py-4">Статус</th>
                        <th class="px-4 py-4">Створено</th>
                        <th class="px-4 py-4 text-right">Дія</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $invoice)
                        @php
                            $currencyCode = $invoice->currency?->code ?? 'CRYPTO';
                            $statusClass = $statusClasses[$invoice->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
                            $statusLabel = $statusLabels[$invoice->status] ?? ucfirst(str_replace('_', ' ', $invoice->status));
                        @endphp
                        <tr class="align-top transition hover:bg-blue-50/30">
                            <td class="px-4 py-5">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-blue-50 text-blue-700">
                                        <x-icon name="file-text" class="h-4 w-4" />
                                    </span>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.invoices.show', $invoice) }}" title="{{ $invoice->uuid }}" class="block max-w-[12rem] truncate font-mono text-sm font-bold leading-5 text-blue-600 hover:text-blue-700 hover:underline">
                                            #{{ \Illuminate\Support\Str::of($invoice->uuid)->explode('-')->first() }}
                                        </a>
                                        @if($invoice->order_id)
                                            <p class="mt-1 max-w-[12rem] truncate text-xs text-slate-500" title="{{ $invoice->order_id }}">Order ID: {{ $invoice->order_id }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-5">
                                @if($invoice->merchant)
                                    <a href="{{ route('admin.merchants.show', $invoice->merchant) }}" class="block max-w-[13rem] break-words text-sm font-black text-slate-950 hover:text-blue-700 hover:underline">
                                        {{ $invoice->merchant->name }}
                                    </a>
                                    <p class="mt-1 max-w-[13rem] truncate text-xs text-slate-500">{{ $invoice->merchant->domain ?: $invoice->merchant->website }}</p>
                                @else
                                    <span class="text-sm font-semibold text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-5">
                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-black text-slate-800">
                                    <x-coin-icon :code="$currencyCode" class="h-6 w-6" />
                                    {{ $currencyCode }}
                                </span>
                            </td>
                            <td class="px-4 py-5">
                                <p class="max-w-[9rem] truncate font-mono text-sm font-black text-slate-950" title="{{ $formatAmount($invoice->amount) }}">{{ $formatAmount($invoice->amount) }}</p>
                                <p class="mt-1 text-xs font-bold text-slate-400">{{ $currencyCode }}</p>
                            </td>
                            <td class="px-4 py-5">
                                <p class="max-w-[9rem] truncate font-mono text-sm font-black {{ (float) $invoice->amount_received > 0 ? 'text-emerald-700' : 'text-slate-500' }}" title="{{ $formatAmount($invoice->amount_received) }}">{{ $formatAmount($invoice->amount_received) }}</p>
                                <p class="mt-1 text-xs font-bold text-slate-400">{{ $currencyCode }}</p>
                            </td>
                            <td class="px-4 py-5">
                                <span class="inline-flex items-center gap-2 whitespace-nowrap rounded-full px-3.5 py-2 text-xs font-black ring-1 {{ $statusClass }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-5">
                                <p class="text-sm font-bold text-slate-800">{{ $invoice->created_at?->format('d.m.Y') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $invoice->created_at?->format('H:i') }}</p>
                            </td>
                            <td class="px-4 py-5 text-right">
                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="inline-flex min-h-10 items-center justify-center rounded-full bg-blue-600 px-5 text-sm font-black text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700">
                                    Відкрити
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="mx-auto max-w-sm">
                                    <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-500">
                                        <x-icon name="file-text" class="h-6 w-6" />
                                    </div>
                                    <p class="mt-4 text-base font-black text-slate-950">Рахунків не знайдено</p>
                                    <p class="mt-1 text-sm text-slate-500">Змініть фільтри або пошуковий запит.</p>
                                    @if(request()->hasAny(['search', 'status', 'currency']))
                                        <a href="{{ route('admin.invoices.index') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-full bg-blue-600 px-6 text-sm font-bold text-white transition hover:bg-blue-700">
                                            Показати всі рахунки
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">{{ $invoices->links() }}</div>
        @endif
    </div>
</div>
@endsection
