@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="space-y-6" x-data="{
    period: {{ $period }},
    labels: {{ Js::from($chartLabels) }},
    revenue: {{ Js::from($chartRevenue) }},
    paid: {{ Js::from($chartPaid) }},
    get maxRev() { return Math.max(...this.revenue, 0.001); },
    get maxPaid() { return Math.max(...this.paid, 1); },
}">

    {{-- ── Header ────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-blue-600">Merchant workspace</p>
            <h1 class="mt-1 text-3xl font-semibold text-slate-950 dark:text-white">{{ $merchant->name }}</h1>
            <p class="mt-1 text-slate-500">Real-time payment overview.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            {{-- Period toggle --}}
            @foreach([7 => '7d', 30 => '30d', 90 => '90d'] as $p => $label)
            <a href="?period={{ $p }}"
               class="inline-flex h-8 items-center rounded-xl px-3 text-xs font-semibold transition
               {{ $period == $p
                    ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/25'
                    : 'border border-slate-200 bg-white text-slate-600 hover:border-blue-200 hover:text-blue-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">
                {{ $label }}
            </a>
            @endforeach
            <x-button href="{{ route('merchant.invoices.create') }}" icon="credit-card">New invoice</x-button>
        </div>
    </div>

    {{-- ── KPI cards ──────────────────────────────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Revenue ({{ $period }}d)</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">
                        {{ number_format((float) $kpi['revenue'], 4) }}
                    </p>
                    <p class="mt-1 text-xs text-slate-400">Today: {{ number_format((float) $kpi['today_revenue'], 4) }}</p>
                </div>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400">
                    <x-icon name="arrow-trend-up" class="h-5 w-5" />
                </span>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Paid ({{ $period }}d)</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ number_format($kpi['paid']) }}</p>
                    <p class="mt-1 text-xs text-slate-400">Today: {{ $kpi['today_paid'] }} payments</p>
                </div>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-400/10 dark:text-blue-400">
                    <x-icon name="check" class="h-5 w-5" />
                </span>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Conversion</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ $kpi['conversion'] }}%</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $kpi['total'] }} total invoices</p>
                </div>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                    <x-icon name="gauge" class="h-5 w-5" />
                </span>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Pending</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ number_format($kpi['pending']) }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $kpi['expired'] }} expired in period</p>
                </div>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-400/10 dark:text-rose-400">
                    <x-icon name="clock" class="h-5 w-5" />
                </span>
            </div>
        </div>
    </div>

    {{-- ── Charts row ─────────────────────────────────────────────── --}}
    <div class="grid gap-6 lg:grid-cols-[1fr_0.45fr]">
        {{-- Revenue over time --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-slate-950 dark:text-white">Revenue over time</p>
                    <p class="text-xs text-slate-400">Confirmed payment volume</p>
                </div>
                <span class="inline-flex items-center gap-1.5 text-xs text-slate-400">
                    <span class="h-2 w-2 rounded-full bg-blue-600"></span> Revenue
                </span>
            </div>
            {{-- Alpine.js SVG bar chart --}}
            <div class="relative h-40 overflow-hidden">
                <template x-if="revenue.filter(v=>v>0).length === 0">
                    <div class="flex h-full items-center justify-center text-sm text-slate-400">No payments in this period yet</div>
                </template>
                <template x-if="revenue.filter(v=>v>0).length > 0">
                    <svg class="h-full w-full" preserveAspectRatio="none" viewBox="0 0 100 100">
                        <defs>
                            <linearGradient id="bar-gradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#2563eb" stop-opacity="0.9"/>
                                <stop offset="100%" stop-color="#2563eb" stop-opacity="0.2"/>
                            </linearGradient>
                        </defs>
                        <template x-for="(v, i) in revenue" :key="i">
                            <rect
                                :x="(i / revenue.length) * 100 + 0.5"
                                :width="(100 / revenue.length) - 1.5"
                                :y="100 - (v / maxRev) * 95"
                                :height="(v / maxRev) * 95"
                                fill="url(#bar-gradient)"
                                rx="1"
                            />
                        </template>
                    </svg>
                </template>
            </div>
            {{-- X-axis labels (first/mid/last) --}}
            <div class="mt-1 flex justify-between text-[10px] text-slate-400">
                <span x-text="labels[0]"></span>
                <span x-text="labels[Math.floor(labels.length/2)]"></span>
                <span x-text="labels[labels.length-1]"></span>
            </div>
        </div>

        {{-- Balances sidebar --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <p class="mb-4 font-semibold text-slate-950 dark:text-white">Available balances</p>
            @if($balances->isEmpty())
                <div class="flex flex-col items-center gap-2 py-6 text-center text-sm text-slate-400">
                    <x-icon name="wallet" class="h-8 w-8 opacity-30" />
                    <p>No payments received yet.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($balances as $balance)
                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/50">
                        <div>
                            <p class="font-semibold text-slate-950 dark:text-white">{{ $balance->currency->code }}</p>
                            <p class="text-xs text-slate-400">{{ $balance->currency->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-mono font-semibold text-slate-950 dark:text-white">{{ $balance->available }}</p>
                            @if($balance->locked > 0)
                                <p class="text-xs text-amber-500">{{ $balance->locked }} locked</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    <a href="{{ route('merchant.balances.index') }}" class="block text-center text-xs text-blue-600 hover:underline">View all balances →</a>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Quick actions row ──────────────────────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
        $actions = [
            ['Payment links', 'Create reusable pay URLs', 'merchant.payment-links.index', 'link'],
            ['Settlement', 'Revenue & fee breakdown', 'merchant.settlement.index', 'coins'],
            ['Widget', 'Embed checkout on your site', 'merchant.widget.index', 'layout'],
            ['Withdrawals', 'Request a payout', 'merchant.withdrawals.index', 'banknote'],
        ];
        @endphp
        @foreach($actions as [$title, $desc, $route, $icon])
        <a href="{{ route($route) }}"
           class="flex items-start gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900/60 dark:hover:border-blue-400/30">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-400/10 dark:text-blue-400">
                <x-icon :name="$icon" class="h-5 w-5" />
            </span>
            <div>
                <p class="font-semibold text-slate-950 dark:text-white">{{ $title }}</p>
                <p class="text-xs text-slate-400">{{ $desc }}</p>
            </div>
        </a>
        @endforeach
    </div>

    {{-- ── Recent invoices ─────────────────────────────────────────── --}}
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <p class="font-semibold text-slate-950 dark:text-white">Recent invoices</p>
            <a href="{{ route('merchant.invoices.index') }}" class="text-xs text-blue-600 hover:underline">View all →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Invoice</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Currency</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wide">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Date</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentInvoices as $inv)
                    <tr class="border-b border-slate-50 transition hover:bg-slate-50/80 dark:border-slate-800/60 dark:hover:bg-slate-800/40">
                        <td class="px-6 py-3 font-mono text-xs text-blue-600">{{ substr($inv->uuid, 0, 8) }}…</td>
                        <td class="px-4 py-3 font-semibold text-slate-950 dark:text-white">{{ $inv->currency->code }}</td>
                        <td class="px-4 py-3 text-right font-mono text-slate-700 dark:text-slate-300">{{ $inv->amount }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$inv->status" /></td>
                        <td class="px-4 py-3 text-xs text-slate-400">{{ $inv->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('merchant.invoices.show', $inv->uuid) }}" class="text-xs text-blue-600 hover:underline">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">No invoices yet. <a href="{{ route('merchant.invoices.create') }}" class="text-blue-600 hover:underline">Create one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
