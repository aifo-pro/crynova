@extends('layouts.app')
@section('title', 'Мерчанти')

@section('content')
@php
    $statusOptions = [
        'unverified' => 'Потребує верифікації',
        'moderation' => 'На модерації',
        'active' => 'Активний',
        'rejected' => 'Відхилено',
        'blocked' => 'Заблоковано',
    ];
    $statusClasses = [
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'red' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-200',
    ];
@endphp

<div>
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                <x-icon name="landmark" class="h-3.5 w-3.5" />
                Адмін-консоль
            </div>
            <h1 class="mt-4 text-3xl font-black tracking-[-0.03em] text-slate-950">Мерчанти</h1>
            <p class="mt-2 max-w-full overflow-hidden text-ellipsis whitespace-nowrap text-sm leading-6 text-slate-500">
                Перегляд, модерація та управління акаунтами мерчантів, статусами проєктів і платіжною активністю.
            </p>
        </div>

        @if($pendingCount > 0)
            <a href="{{ route('admin.merchants.index', ['status' => 'moderation']) }}" class="inline-flex w-fit shrink-0 items-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-bold text-amber-700 shadow-sm transition hover:bg-amber-100">
                <x-icon name="clock" class="h-4 w-4" />
                {{ $pendingCount }} на модерації
            </a>
        @endif
    </div>

    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4" style="margin-top: 32px;">
        @foreach([
            ['Усього мерчантів', $stats['total'] ?? 0, 'landmark', 'text-blue-700 bg-blue-50'],
            ['Активні', $stats['active'] ?? 0, 'check', 'text-emerald-700 bg-emerald-50'],
            ['На модерації', $stats['moderation'] ?? 0, 'clock', 'text-amber-700 bg-amber-50'],
            ['Заблоковані', $stats['blocked'] ?? 0, 'shield-off', 'text-rose-700 bg-rose-50'],
        ] as [$label, $value, $icon, $tone])
            <div class="min-h-32 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase leading-5 tracking-[0.14em] text-slate-400">{{ $label }}</p>
                        <p class="mt-3 text-3xl font-black tracking-[-0.04em] text-slate-950">{{ number_format($value) }}</p>
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
            <div class="relative min-w-0 flex-1">
                <input name="search" value="{{ request('search') }}" class="fin-input w-full rounded-2xl pl-4" placeholder="Пошук за назвою, доменом або власником...">
            </div>
            <select name="status" class="fin-input rounded-2xl xl:w-56">
                <option value="">Усі статуси</option>
                @foreach($statusOptions as $val => $label)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                @endforeach
            </select>
            @if(request()->hasAny(['search', 'status']))
                <div class="flex gap-2 xl:w-auto">
                    <x-button type="submit" variant="secondary" class="rounded-2xl px-6">Фільтр</x-button>
                    <a href="{{ route('admin.merchants.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 transition hover:border-blue-200 hover:text-blue-700">Скинути</a>
                </div>
            @else
                <x-button type="submit" variant="secondary" class="rounded-2xl px-7 xl:w-auto">Фільтр</x-button>
            @endif
        </form>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" style="margin-top: 34px;">
        <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/70 px-6 py-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-950">Список мерчантів</h2>
                <p class="mt-1 text-sm text-slate-500">Показано {{ $merchants->count() }} із {{ $merchants->total() }} записів.</p>
            </div>
            <span class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Admin review</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 bg-white text-xs font-black uppercase tracking-[0.12em] text-slate-400">
                        <th class="px-6 py-4">Мерчант</th>
                        <th class="px-6 py-4">Власник</th>
                        <th class="px-6 py-4">Тип</th>
                        <th class="px-6 py-4">Комісія</th>
                        <th class="px-6 py-4">Рахунки</th>
                        <th class="px-6 py-4">Статус</th>
                        <th class="px-6 py-4 text-right">Дія</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($merchants as $merchant)
                        @php
                            $sm = $merchant->statusMeta();
                            $badgeClass = $statusClasses[$sm['color']] ?? $statusClasses['slate'];
                        @endphp
                        <tr class="transition hover:bg-blue-50/30">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-blue-600 text-sm font-black text-white shadow-lg shadow-blue-100">
                                        {{ strtoupper(substr($merchant->name ?: 'M', 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-slate-950">{{ $merchant->name }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ $merchant->merchant_type === 'telegram' ? '@'.$merchant->telegram_channel : $merchant->domain }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <p class="max-w-56 truncate text-sm font-semibold text-slate-700">{{ $merchant->user?->email ?? '—' }}</p>
                            </td>
                            <td class="px-6 py-5">
                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ ucfirst($merchant->merchant_type) }}</span>
                            </td>
                            <td class="px-6 py-5 font-mono text-sm font-semibold text-slate-700">{{ $merchant->fee_percent }}%</td>
                            <td class="px-6 py-5 text-sm font-black text-slate-950">{{ number_format($merchant->invoices_count) }}</td>
                            <td class="px-6 py-5">
                                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3.5 py-1.5 text-xs font-bold ring-1 {{ $badgeClass }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ $sm['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <a href="{{ route('admin.merchants.show', $merchant) }}" class="inline-flex items-center justify-center rounded-full bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700">
                                    Відкрити
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center">
                                <div class="mx-auto max-w-sm">
                                    <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-500">
                                        <x-icon name="landmark" class="h-5 w-5" />
                                    </div>
                                    <p class="mt-4 font-bold text-slate-950">Мерчантів не знайдено</p>
                                    <p class="mt-1 text-sm text-slate-500">Змініть фільтри або пошуковий запит.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($merchants->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">{{ $merchants->links() }}</div>
        @endif
    </div>
</div>
@endsection
