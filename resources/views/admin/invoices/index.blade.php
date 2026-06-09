@extends('layouts.app')
@section('title', 'Рахунки — Адмін')

@section('content')
@php
    $statusLabels = [
        'pending' => 'Очікує',
        'waiting_confirmations' => 'Підтвердження',
        'paid' => 'Оплачено',
        'underpaid' => 'Недоплата',
        'overpaid' => 'Переплата',
        'expired' => 'Прострочено',
        'failed' => 'Помилка',
        'refunded' => 'Повернено',
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
    ];
@endphp

<div>
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                <x-icon name="file-text" class="h-3.5 w-3.5" />
                Адмін-панель
            </div>
            <h1 class="mt-4 text-3xl font-black tracking-[-0.03em] text-slate-950">Рахунки</h1>
            <p class="mt-2 max-w-full overflow-hidden text-ellipsis whitespace-nowrap text-sm leading-6 text-slate-500">
                Глобальний моніторинг рахунків, статусів оплат і webhook-подій по всіх мерчантах.
            </p>
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4" style="margin-top: 32px;">
        @foreach([
            ['Усього рахунків', $stats['total'] ?? 0, 'file-text', 'text-blue-700 bg-blue-50'],
            ['Оплачено', $stats['paid'] ?? 0, 'check', 'text-emerald-700 bg-emerald-50'],
            ['В очікуванні', $stats['pending'] ?? 0, 'clock', 'text-amber-700 bg-amber-50'],
            ['Отримано всього', number_format((float) ($stats['volume'] ?? 0), 2), 'banknote', 'text-cyan-700 bg-cyan-50'],
        ] as [$label, $value, $icon, $tone])
            <div class="min-h-32 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase leading-5 tracking-[0.14em] text-slate-400">{{ $label }}</p>
                        <p class="mt-3 text-3xl font-black tracking-[-0.04em] text-slate-950">{{ $value }}</p>
                    </div>
                    <span class="grid h-12 w-12 place-items-center rounded-2xl {{ $tone }}">
                        <x-icon name="{{ $icon }}" class="h-5 w-5" />
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm" style="margin-top: 34px;">
        <form method="GET" class="flex flex-col gap-4 xl:flex-row xl:items-center">
            <div class="min-w-0 flex-1">
                <input name="search" value="{{ request('search') }}" class="fin-input w-full rounded-2xl" placeholder="Пошук UUID або order ID...">
            </div>
            <select name="status" class="fin-input rounded-2xl xl:w-56">
                <option value="">Статус: усі</option>
                @foreach($statuses as $s)
                    <option value="{{ $s }}" @selected(request('status') == $s)>{{ $statusLabels[$s] ?? ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
            <select name="currency" class="fin-input rounded-2xl xl:w-48">
                <option value="">Валюта: усі</option>
                @foreach($currencies as $c)
                    <option value="{{ $c->code }}" @selected(request('currency') == $c->code)>{{ $c->code }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <x-button type="submit" variant="secondary" class="rounded-2xl px-7">Фільтр</x-button>
                @if(request()->hasAny(['search', 'status', 'currency']))
                    <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 transition hover:border-blue-200 hover:text-blue-700">Скинути</a>
                @endif
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" style="margin-top: 34px;">
        <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/70 px-6 py-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-950">Список рахунків</h2>
                <p class="mt-1 text-sm text-slate-500">Показано {{ $invoices->count() }} із {{ $invoices->total() }} записів.</p>
            </div>
            <span class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Invoice monitoring</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 bg-white text-xs font-black uppercase tracking-[0.12em] text-slate-400">
                        <th class="px-6 py-4">UUID</th>
                        <th class="px-6 py-4">Мерчант</th>
                        <th class="px-6 py-4">Валюта</th>
                        <th class="px-6 py-4">Сума</th>
                        <th class="px-6 py-4">Отримано</th>
                        <th class="px-6 py-4">Статус</th>
                        <th class="px-6 py-4">Створено</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $inv)
                        @php
                            $statusClass = $statusClasses[$inv->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
                            $statusLabel = $statusLabels[$inv->status] ?? ucfirst(str_replace('_', ' ', $inv->status));
                        @endphp
                        <tr class="transition hover:bg-blue-50/30">
                            <td class="px-6 py-5">
                                <a href="{{ route('admin.invoices.show', $inv) }}" class="font-mono text-sm font-bold text-blue-600 hover:text-blue-700 hover:underline">
                                    {{ substr($inv->uuid, 0, 8) }}...
                                </a>
                                @if($inv->order_id)
                                    <p class="mt-1 max-w-32 truncate text-xs text-slate-400">{{ $inv->order_id }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-5">
                                <p class="max-w-48 truncate text-sm font-bold text-slate-950">{{ $inv->merchant?->name ?? '—' }}</p>
                            </td>
                            <td class="px-6 py-5">
                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ $inv->currency?->code ?? '—' }}</span>
                            </td>
                            <td class="px-6 py-5 font-mono text-sm font-semibold text-slate-800">{{ $inv->amount }}</td>
                            <td class="px-6 py-5 font-mono text-sm font-semibold {{ $inv->amount_received > 0 ? 'text-emerald-700' : 'text-slate-400' }}">
                                {{ $inv->amount_received > 0 ? $inv->amount_received : '—' }}
                            </td>
                            <td class="px-6 py-5">
                                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3.5 py-1.5 text-xs font-bold ring-1 {{ $statusClass }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-sm text-slate-500">{{ $inv->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="mx-auto max-w-sm">
                                    <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-500">
                                        <x-icon name="file-text" class="h-5 w-5" />
                                    </div>
                                    <p class="mt-4 font-bold text-slate-950">Рахунків не знайдено</p>
                                    <p class="mt-1 text-sm text-slate-500">Змініть фільтри або пошуковий запит.</p>
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
