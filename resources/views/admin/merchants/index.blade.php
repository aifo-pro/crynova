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

    $hasFilters = request()->filled('search') || request()->filled('status');
    $activePercent = ($stats['total'] ?? 0) > 0 ? round((($stats['active'] ?? 0) / $stats['total']) * 100) : 0;
    $statCards = [
        ['label' => 'Усього', 'value' => $stats['total'] ?? 0, 'icon' => 'landmark', 'tone' => 'bg-blue-50 text-blue-700', 'meta' => 'Всі проєкти'],
        ['label' => 'Активні', 'value' => $stats['active'] ?? 0, 'icon' => 'check', 'tone' => 'bg-emerald-50 text-emerald-700', 'meta' => $activePercent.'% від бази'],
        ['label' => 'Модерація', 'value' => $stats['moderation'] ?? 0, 'icon' => 'clock', 'tone' => 'bg-amber-50 text-amber-700', 'meta' => 'Потрібна дія'],
        ['label' => 'Заблоковані', 'value' => $stats['blocked'] ?? 0, 'icon' => 'shield-off', 'tone' => 'bg-rose-50 text-rose-700', 'meta' => 'Ризик-контроль'],
    ];
@endphp

<div class="space-y-7">
    <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm shadow-slate-200/70">
        <div class="border-b border-slate-100 bg-gradient-to-b from-slate-50 to-white px-6 py-7 sm:px-8">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                        <x-icon name="landmark" class="h-3.5 w-3.5" />
                        Адмін-консоль
                    </div>
                    <h1 class="mt-4 text-3xl font-black tracking-[-0.03em] text-slate-950 sm:text-4xl">Мерчанти</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                        Модерація проєктів, контроль статусів, комісій та платіжної активності мерчантів.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @if($pendingCount > 0)
                        <a href="{{ route('admin.merchants.index', ['status' => 'moderation']) }}" class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-bold text-amber-700 transition hover:bg-amber-100">
                            <x-icon name="clock" class="h-4 w-4" />
                            {{ $pendingCount }} на модерації
                        </a>
                    @endif
                    @if($hasFilters)
                        <a href="{{ route('admin.merchants.index') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:border-blue-200 hover:text-blue-700">
                            Скинути фільтри
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid gap-px bg-slate-100 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($statCards as $card)
                <div class="bg-white p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">{{ $card['label'] }}</p>
                            <p class="mt-3 text-3xl font-black tracking-[-0.04em] text-slate-950">{{ number_format($card['value']) }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $card['meta'] }}</p>
                        </div>
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl {{ $card['tone'] }}">
                            <x-icon :name="$card['icon']" class="h-5 w-5" />
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/60 sm:p-5">
        <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_14rem_auto] lg:items-center">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input name="search" value="{{ request('search') }}" class="fin-input h-12 rounded-2xl pl-11" placeholder="Пошук за назвою, доменом або email власника">
            </div>

            <select name="status" class="fin-input h-12 rounded-2xl">
                <option value="">Усі статуси</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <div class="flex gap-2">
                <x-button type="submit" class="h-12 rounded-2xl px-7">Фільтр</x-button>
                @if($hasFilters)
                    <a href="{{ route('admin.merchants.index') }}" class="inline-flex h-12 items-center justify-center rounded-2xl border border-slate-200 px-5 text-sm font-bold text-slate-600 transition hover:border-blue-200 hover:text-blue-700">Очистити</a>
                @endif
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm shadow-slate-200/70">
        <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/70 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-950">Список мерчантів</h2>
                <p class="mt-1 text-sm text-slate-500">Показано {{ $merchants->count() }} із {{ $merchants->total() }} записів.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-500 ring-1 ring-slate-200">
                <span class="h-1.5 w-1.5 rounded-full bg-blue-600"></span>
                Admin review
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] text-left">
                <thead class="bg-white">
                    <tr class="border-b border-slate-100 text-xs font-black uppercase tracking-[0.13em] text-slate-400">
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
                            $statusMeta = $merchant->statusMeta();
                            $badgeClass = $statusClasses[$statusMeta['color']] ?? $statusClasses['slate'];
                            $typeLabel = $merchant->merchant_type === 'telegram' ? 'Telegram' : 'Domain';
                            $destination = $merchant->merchant_type === 'telegram'
                                ? ($merchant->telegram_channel ? '@'.$merchant->telegram_channel : 'Telegram channel')
                                : ($merchant->domain ?: $merchant->website ?: 'Domain not set');
                        @endphp
                        <tr class="transition hover:bg-blue-50/35">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-blue-600 text-sm font-black text-white shadow-sm">
                                        {{ mb_strtoupper(mb_substr($merchant->name ?: 'M', 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-black text-slate-950">{{ $merchant->name }}</p>
                                        <p class="mt-0.5 truncate text-xs font-medium text-slate-500">{{ $destination }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <p class="max-w-56 truncate text-sm font-bold text-slate-800">{{ $merchant->user?->email ?? '—' }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">ID #{{ $merchant->user_id }}</p>
                            </td>
                            <td class="px-6 py-5">
                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ $typeLabel }}</span>
                            </td>
                            <td class="px-6 py-5 font-mono text-sm font-bold text-slate-800">{{ $merchant->fee_percent }}%</td>
                            <td class="px-6 py-5">
                                <span class="font-black text-slate-950">{{ number_format($merchant->invoices_count) }}</span>
                            </td>
                            <td class="px-6 py-5">
                                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3.5 py-1.5 text-xs font-black ring-1 {{ $badgeClass }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ $statusMeta['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <a href="{{ route('admin.merchants.show', $merchant) }}" class="inline-flex h-10 items-center justify-center rounded-full bg-blue-600 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">
                                    Відкрити
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-20">
                                <div class="mx-auto max-w-md text-center">
                                    <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-blue-50 text-blue-700 ring-1 ring-blue-100">
                                        <x-icon name="landmark" class="h-6 w-6" />
                                    </div>
                                    <h3 class="mt-5 text-lg font-black text-slate-950">Мерчантів не знайдено</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">
                                        @if($hasFilters)
                                            За поточними фільтрами немає результатів. Очистіть пошук або змініть статус.
                                        @else
                                            Нові проєкти зʼявляться тут після створення мерчанта користувачем.
                                        @endif
                                    </p>
                                    @if($hasFilters)
                                        <a href="{{ route('admin.merchants.index') }}" class="mt-5 inline-flex h-11 items-center justify-center rounded-full bg-blue-600 px-6 text-sm font-bold text-white transition hover:bg-blue-700">
                                            Очистити фільтри
                                        </a>
                                    @endif
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
    </section>
</div>
@endsection
