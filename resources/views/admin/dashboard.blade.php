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

    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <x-card title="Обсяг платформи" subtitle="Платежі та підтвердження за період">
            <div class="line-chart relative h-72 overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900/40">
                <svg class="h-full w-full" viewBox="0 0 720 260" preserveAspectRatio="none">
                    <path d="M20 215C82 172 104 158 164 166C226 174 264 88 324 100C386 112 420 146 478 116C536 86 590 60 700 42" fill="none" stroke="#2563EB" stroke-width="8" stroke-linecap="round"/>
                    <path d="M20 232C96 220 136 192 200 202C268 212 304 158 364 168C430 178 468 128 528 138C590 148 626 92 700 86" fill="none" stroke="#10B981" stroke-width="8" stroke-linecap="round" opacity=".75"/>
                </svg>
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
