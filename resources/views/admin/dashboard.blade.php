@extends('layouts.app')
@section('title', 'Адмін-панель')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <x-badge variant="blue">Центр управління</x-badge>
            <h1 class="mt-4 text-4xl font-semibold text-slate-950 dark:text-white">Операції платформи</h1>
            <p class="mt-2 max-w-2xl text-slate-600 dark:text-slate-300">Контроль мерчантів, рахунків, гаманців, виплат, аудиту та черг ризику.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-button href="{{ route('admin.withdrawals.index') }}" icon="banknote">Перевірити виплати</x-button>
            <x-button href="{{ route('admin.audit-logs.index') }}" variant="secondary" icon="book">Журнал аудиту</x-button>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <x-metric-card label="Усього мерчантів" :value="$stats['total_merchants']" icon="landmark" />
        <x-metric-card label="Активних мерчантів" :value="$stats['active_merchants']" icon="check" />
        <x-metric-card label="Рахунків сьогодні" :value="$stats['invoices_today']" icon="file-text" />
        <x-metric-card label="Оплачено сьогодні" :value="$stats['paid_today']" icon="wallet" />
        <x-metric-card label="Виплати в очікуванні" :value="$stats['pending_withdrawals']" icon="banknote" />
    </div>

    {{-- Attention centre: everything that needs an admin action, one click away. --}}
    @php
        $attentionTones = [
            'amber'   => 'border-amber-200 bg-amber-50 text-amber-700',
            'blue'    => 'border-blue-200 bg-blue-50 text-blue-700',
            'violet'  => 'border-violet-200 bg-violet-50 text-violet-700',
            'cyan'    => 'border-cyan-200 bg-cyan-50 text-cyan-700',
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'rose'    => 'border-rose-200 bg-rose-50 text-rose-700',
            'slate'   => 'border-slate-200 bg-slate-50 text-slate-600',
        ];
        $attentionTotal = collect($attention)->sum('count');
    @endphp
    <x-card title="Потребують уваги" subtitle="Задачі, що очікують дії адміністратора">
        @if($attentionTotal === 0)
            <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">
                <x-icon name="shield-check" class="h-5 w-5" /> Все під контролем — активних задач немає.
            </div>
        @else
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($attention as $item)
                    @php $active = $item['count'] > 0; $tone = $active ? ($attentionTones[$item['tone']] ?? $attentionTones['slate']) : 'border-slate-200 bg-white text-slate-400'; @endphp
                    <a href="{{ $item['url'] }}" class="group flex items-center gap-3 rounded-2xl border p-4 transition hover:shadow-sm {{ $tone }}">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white/70 ring-1 ring-black/5">
                            <x-icon :name="$item['icon']" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold {{ $active ? '' : 'text-slate-500' }}">{{ $item['label'] }}</p>
                            <p class="text-2xl font-black leading-tight {{ $active ? '' : 'text-slate-300' }}">{{ number_format($item['count']) }}</p>
                        </div>
                        @if($active)<x-icon name="arrow-right" class="h-4 w-4 shrink-0 opacity-0 transition group-hover:opacity-100" />@endif
                    </a>
                @endforeach
            </div>
        @endif
    </x-card>

    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <x-card title="Оплачені рахунки" subtitle="Динаміка за останні 7 днів">
            @php $trendMax = max(1, collect($trend)->max('count')); @endphp
            <div class="flex h-72 items-end gap-2 rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900/40">
                @foreach($trend as $point)
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <span class="text-xs font-bold text-slate-500">{{ $point['count'] }}</span>
                        <div class="flex w-full flex-1 items-end">
                            <div class="w-full rounded-t-lg bg-gradient-to-t from-blue-600 to-cyan-500 transition-all"
                                 style="height: {{ max(4, round($point['count'] / $trendMax * 100)) }}%"></div>
                        </div>
                        <span class="text-[11px] font-semibold text-slate-400">{{ $point['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card title="Черга ризику" subtitle="Потребують уваги адміністратора">
            <div class="space-y-3">
                @forelse($pendingWithdrawals as $wd)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-400/20 dark:bg-amber-400/10">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-semibold text-slate-950 dark:text-white">{{ $wd->merchant->name }}</p>
                                <p class="mt-1 font-mono text-xs text-amber-700 dark:text-amber-100">{{ $wd->amount }} {{ $wd->currency->code }} → {{ substr($wd->to_address, 0, 18) }}...</p>
                            </div>
                            <x-button href="{{ route('admin.withdrawals.index') }}" variant="warning">Перевірити</x-button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Немає виплат на перевірці.</p>
                @endforelse
            </div>
        </x-card>
    </div>

    <x-card title="Останні рахунки" subtitle="Глобальна платіжна активність зі статусами">
        <x-table :headers="['UUID', 'Мерчант', 'Валюта', 'Сума', 'Статус', 'Створено']">
            @forelse($recentInvoices as $inv)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/60">
                    <td class="px-4 py-3 font-mono text-xs text-blue-600">{{ substr($inv->uuid, 0, 8) }}</td>
                    <td class="px-4 py-3 font-semibold">{{ $inv->merchant->name }}</td>
                    <td class="px-4 py-3">{{ optional($inv->currency)->code ?? $inv->price_currency ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono">{{ $inv->amount ?? $inv->price_amount }}</td>
                    <td class="px-4 py-3"><x-status-badge :status="$inv->status" /></td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $inv->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Рахунків ще немає.</td></tr>
            @endforelse
        </x-table>
    </x-card>
</div>
@endsection
