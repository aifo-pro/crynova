@extends('layouts.app')
@section('title', 'Settlement')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-950 dark:text-white">Settlement</h1>
            <p class="mt-1 text-slate-500">Revenue breakdown, platform fees and net payable amount.</p>
        </div>
        {{-- Date filter --}}
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <div>
                <label class="fin-label text-xs">From</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="fin-input py-1.5 text-sm">
            </div>
            <div>
                <label class="fin-label text-xs">To</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="fin-input py-1.5 text-sm">
            </div>
            <x-button type="submit" variant="secondary">Apply</x-button>
        </form>
    </div>

    {{-- ── Summary cards ──────────────────────────────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400">
                <x-icon name="arrow-trend-up" class="h-5 w-5" />
            </div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Gross received</p>
            <p class="mt-1 text-2xl font-semibold text-slate-950 dark:text-white">{{ number_format((float) $gross, 8) }}</p>
            <p class="mt-0.5 text-xs text-slate-400">{{ $paidInvoices->count() }} payments</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                <x-icon name="coins" class="h-5 w-5" />
            </div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Platform fee</p>
            <p class="mt-1 text-2xl font-semibold text-amber-600 dark:text-amber-400">{{ number_format((float) $fees, 8) }}</p>
            <p class="mt-0.5 text-xs text-slate-400">{{ $merchant->fee_percent }}% rate</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-400/10 dark:text-blue-400">
                <x-icon name="wallet" class="h-5 w-5" />
            </div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Net to you</p>
            <p class="mt-1 text-2xl font-semibold text-blue-600 dark:text-blue-400">{{ number_format((float) $net, 8) }}</p>
            <p class="mt-0.5 text-xs text-slate-400">After fees</p>
        </div>
    </div>

    {{-- ── Charts row ─────────────────────────────────────────────────── --}}
    @if($dailyData->isNotEmpty())
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/60"
         x-data="{
            days:    {{ Js::from($dailyData->pluck('day')) }},
            revenue: {{ Js::from($dailyData->pluck('revenue')) }},
            get maxRev() { return Math.max(...this.revenue.map(Number), 0.001); }
         }">
        <p class="mb-4 font-semibold text-slate-950 dark:text-white">Daily revenue</p>
        <div class="relative h-32">
            <svg class="h-full w-full" preserveAspectRatio="none" viewBox="0 0 100 100">
                <defs>
                    <linearGradient id="settle-grad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#2563eb" stop-opacity="0.8"/>
                        <stop offset="100%" stop-color="#2563eb" stop-opacity="0.1"/>
                    </linearGradient>
                </defs>
                <template x-for="(v, i) in revenue" :key="i">
                    <rect
                        :x="(i / revenue.length) * 100 + 0.3"
                        :width="(100 / revenue.length) - 1"
                        :y="100 - (Number(v) / maxRev) * 95"
                        :height="(Number(v) / maxRev) * 95"
                        fill="url(#settle-grad)" rx="1" />
                </template>
            </svg>
        </div>
        <div class="mt-1 flex justify-between text-[10px] text-slate-400">
            <span x-text="days[0]"></span>
            <span x-text="days[Math.floor(days.length/2)]"></span>
            <span x-text="days[days.length-1]"></span>
        </div>
    </div>
    @endif

    {{-- ── Per-currency breakdown ─────────────────────────────────────── --}}
    @if($byCurrency->isNotEmpty())
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <p class="font-semibold text-slate-950 dark:text-white">By currency</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Currency</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wide">Payments</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wide">Gross</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wide">Net</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($byCurrency as $row)
                    <tr class="border-b border-slate-50 dark:border-slate-800/60">
                        <td class="px-6 py-3 font-semibold text-slate-950 dark:text-white">
                            {{ $row->currency->code }}
                            <span class="ml-1 text-xs font-normal text-slate-400">{{ $row->currency->network }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $row->count }}</td>
                        <td class="px-4 py-3 text-right font-mono text-slate-700 dark:text-slate-300">{{ number_format((float) $row->gross, 8) }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-blue-600 dark:text-blue-400">{{ number_format((float) $row->net, 8) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ── Recent withdrawals in period ──────────────────────────────── --}}
    @if($withdrawals->isNotEmpty())
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <p class="font-semibold text-slate-950 dark:text-white">Payouts in period</p>
            <a href="{{ route('merchant.withdrawals.index') }}" class="text-xs text-blue-600 hover:underline">View all →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Currency</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wide">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($withdrawals as $w)
                    <tr class="border-b border-slate-50 dark:border-slate-800/60">
                        <td class="px-6 py-3 text-xs text-slate-500">{{ $w->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-950 dark:text-white">{{ $w->currency->code }}</td>
                        <td class="px-4 py-3 text-right font-mono text-slate-700 dark:text-slate-300">{{ $w->amount }}</td>
                        <td class="px-4 py-3"><x-badge>{{ ucfirst($w->status) }}</x-badge></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($paidInvoices->isEmpty())
    <x-alert variant="info">No paid invoices in the selected period. Adjust the date range.</x-alert>
    @endif
</div>
@endsection
