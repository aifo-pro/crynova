@extends('layouts.app')
@section('title', __('account.dashboard.title'))

@section('content')
@php
    $chartRows = collect($chart);
    $hasChartData = $chartRows->sum('created') > 0 || $chartRows->sum('paid') > 0;
    $maxChart = max(100, $chartRows->max(fn ($point) => max($point['created'], $point['paid'])));
    $chartWidth = 700;
    $chartHeight = 230;
    $xStep = $chartWidth / max(1, $chartRows->count() - 1);
    $yFor = fn ($value) => 28 + ($chartHeight - 56) - (($value / $maxChart) * ($chartHeight - 56));
    $createdPoints = $chartRows->values()->map(fn ($point, $i) => round($i * $xStep, 2).','.round($yFor($point['created']), 2))->implode(' ');
    $paidPoints = $chartRows->values()->map(fn ($point, $i) => round($i * $xStep, 2).','.round($yFor($point['paid']), 2))->implode(' ');
    $createdArea = '0,'.$chartHeight.' '.$createdPoints.' '.$chartWidth.','.$chartHeight;
    $paidArea = '0,'.$chartHeight.' '.$paidPoints.' '.$chartWidth.','.$chartHeight;
    $formatBalanceAmount = function ($value): string {
        $number = (float) $value;
        $decimals = abs($number) >= 1 ? 2 : 8;
        $formatted = number_format($number, $decimals, '.', '');

        return $decimals > 2 ? (rtrim(rtrim($formatted, '0'), '.') ?: '0') : $formatted;
    };

    $invoiceRows = $recentInvoices->map(fn ($invoice) => [
            'id' => $invoice->order_id ?: 'INV-'.str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT),
            'project' => $invoice->merchant?->name ?? '-',
            'amount' => rtrim(rtrim(number_format((float) $invoice->amount, 8, '.', ''), '0'), '.'),
            'currency' => $invoice->currency?->code ?? 'USD',
            'status' => $invoice->status,
            'date' => $invoice->created_at?->format('d.m.Y'),
        ]);

    $balanceRows = $balances->map(fn ($row) => [
            'code' => $row['currency']?->code ?? 'USD',
            'name' => $row['currency']?->name ?? '',
            'network' => $row['currency']?->network ?? '',
            'amount' => $formatBalanceAmount($row['available']),
        ]);

    $statusMeta = [
        'paid' => ['Оплачено', 'bg-emerald-100 text-emerald-700'],
        'underpaid' => ['Частково', 'bg-orange-100 text-orange-700'],
        'overpaid' => ['Оплачено', 'bg-emerald-100 text-emerald-700'],
        'pending' => ['Очікує', 'bg-blue-100 text-blue-700'],
        'waiting_confirmations' => ['Очікує', 'bg-blue-100 text-blue-700'],
        'expired' => ['Минув', 'bg-slate-100 text-slate-600'],
        'failed' => ['Помилка', 'bg-rose-100 text-rose-700'],
    ];
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">{{ __('account.dashboard.title') }}</h1>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="relative flex min-h-[9.5rem] flex-col overflow-hidden rounded-2xl bg-blue-600 p-5 text-white shadow-lg shadow-blue-600/20 sm:p-6">
            <div class="absolute -right-8 -top-10 h-32 w-32 rounded-full bg-white/10"></div>
            <div class="relative z-10 flex flex-1 flex-col">
                <p class="text-sm font-medium text-blue-100">{{ __('account.dashboard.balance') }}</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight">$ {{ number_format((float) $stats['balance'], 2) }}</p>
                <div class="mt-auto flex items-end justify-between gap-3 pt-4">
                    <p class="text-sm font-medium text-blue-50">Реальний баланс</p>
                    <svg class="h-8 w-20 shrink-0 opacity-80" viewBox="0 0 120 42" fill="none" aria-hidden="true">
                        <path d="M2 32C15 31 19 22 30 25C40 28 42 38 52 30C62 22 63 7 72 16C80 24 82 31 90 25C99 18 100 7 108 12C113 15 114 27 118 24" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
        </div>

        @foreach([
            [__('account.dashboard.created_invoices'), $stats['created'], 'Всього створено', 'text-slate-500', '#2563EB'],
            [__('account.dashboard.paid_invoices'), $stats['paid'], 'Успішно оплачено', 'text-emerald-600', '#10B981'],
            [__('account.dashboard.partial_paid'), $stats['partial'], 'Часткові оплати', 'text-orange-600', '#F97316'],
        ] as [$label, $value, $change, $changeClass, $stroke])
            <div class="flex min-h-[9.5rem] flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ number_format($value) }}</p>
                <div class="mt-auto flex items-end justify-between gap-3 pt-4">
                    <p class="text-sm font-medium {{ $changeClass }}">{{ $change }}</p>
                    <svg class="h-8 w-20 shrink-0 opacity-80" viewBox="0 0 120 42" fill="none" aria-hidden="true">
                        <path d="M2 34H20L27 12L41 31L53 28L62 34L73 16L88 33L101 23L116 30" stroke="{{ $stroke }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(18rem,0.9fr)]">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-semibold text-slate-950">{{ __('account.dashboard.invoice_analytics') }}</h2>
                <div class="flex flex-wrap items-center gap-5 text-xs font-medium text-slate-500">
                    <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-blue-600"></span>{{ __('account.dashboard.created_series') }}</span>
                    <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ __('account.dashboard.paid_series') }}</span>
                    <span class="rounded-xl bg-slate-100 px-3 py-2 text-slate-600">7 днів</span>
                </div>
            </div>

            @if($hasChartData)
            <div class="relative overflow-hidden rounded-2xl bg-white px-4 pb-7 pt-2">
                <svg viewBox="-34 0 {{ $chartWidth + 44 }} {{ $chartHeight + 34 }}" class="h-72 w-full overflow-visible">
                    <defs>
                        <linearGradient id="createdFill" x1="0" y1="0" x2="0" y2="1">
                            <stop stop-color="#2563EB" stop-opacity="0.20"/>
                            <stop offset="1" stop-color="#2563EB" stop-opacity="0"/>
                        </linearGradient>
                        <linearGradient id="paidFill" x1="0" y1="0" x2="0" y2="1">
                            <stop stop-color="#10B981" stop-opacity="0.20"/>
                            <stop offset="1" stop-color="#10B981" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    @foreach([0, 25, 50, 75, 100] as $tick)
                        @php $y = $yFor($tick); @endphp
                        <line x1="0" y1="{{ $y }}" x2="{{ $chartWidth }}" y2="{{ $y }}" stroke="#E2E8F0" stroke-width="1"/>
                        <text x="-22" y="{{ $y + 4 }}" fill="#64748B" font-size="12">{{ $tick }}</text>
                    @endforeach
                    <polygon points="{{ $createdArea }}" fill="url(#createdFill)"/>
                    <polygon points="{{ $paidArea }}" fill="url(#paidFill)"/>
                    <polyline points="{{ $createdPoints }}" fill="none" stroke="#2563EB" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="{{ $paidPoints }}" fill="none" stroke="#10B981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    @foreach($chartRows->values() as $point)
                        @php $x = $loop->index * $xStep; @endphp
                        <circle cx="{{ $x }}" cy="{{ $yFor($point['created']) }}" r="4" fill="#2563EB"/>
                        <circle cx="{{ $x }}" cy="{{ $yFor($point['paid']) }}" r="4" fill="#10B981"/>
                        <text x="{{ $x - 16 }}" y="{{ $chartHeight + 20 }}" fill="#64748B" font-size="12">{{ \Illuminate\Support\Carbon::parse($point['day'])->format('d.m') }}</text>
                    @endforeach
                </svg>
            </div>
            @else
                <div class="flex h-64 flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 text-center sm:h-72">
                    <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm">
                        <x-icon name="gauge" class="h-5 w-5" />
                    </span>
                    <p class="font-semibold text-slate-950">Немає даних для графіка</p>
                    <p class="mt-2 max-w-sm text-sm text-slate-500">Аналітика зʼявиться після створення рахунків.</p>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><x-icon name="book" class="h-4 w-4" /></span>
                <h2 class="text-lg font-semibold text-slate-950">FAQ</h2>
            </div>
            <p class="mb-5 text-sm leading-6 text-slate-500">{{ __('account.dashboard.faq_text') }}</p>
            <ul class="space-y-3 text-sm">
                @foreach(__('account.dashboard.faq') as $question)
                    <li><a href="{{ route('developers') }}" class="flex items-start gap-3 font-medium text-blue-600 hover:text-blue-700"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600"></span>{{ $question }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-semibold text-slate-950">Останні рахунки</h2>
                <a href="{{ route('account.payments') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Переглянути всі</a>
            </div>
            @if($invoiceRows->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full table-fixed text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                                <th class="pb-3 pr-2">ID</th>
                                <th class="hidden pb-3 pr-2 sm:table-cell">Проєкт</th>
                                <th class="pb-3 pr-2">Сума</th>
                                <th class="pb-3 pr-2">Валюта</th>
                                <th class="pb-3 pr-2 text-right">Статус</th>
                                <th class="hidden pb-3 text-right lg:table-cell">Дата</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($invoiceRows as $row)
                                @php $meta = $statusMeta[$row['status']] ?? [$row['status'], 'bg-slate-100 text-slate-600']; @endphp
                                <tr>
                                    <td class="truncate py-3 pr-2 font-medium text-slate-950">{{ $row['id'] }}</td>
                                    <td class="hidden truncate py-3 pr-2 text-slate-700 sm:table-cell">{{ $row['project'] }}</td>
                                    <td class="truncate py-3 pr-2 font-mono text-slate-700">{{ $row['amount'] }}</td>
                                    <td class="py-3 pr-2 text-slate-700">{{ $row['currency'] }}</td>
                                    <td class="py-3 pr-2 text-right"><span class="whitespace-nowrap rounded-lg px-2 py-1 text-xs font-semibold {{ $meta[1] }}">{{ $meta[0] }}</span></td>
                                    <td class="hidden py-3 text-right text-xs text-slate-500 lg:table-cell">{{ $row['date'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-5 text-sm text-slate-400">Показано 1 - {{ $invoiceRows->count() }} з {{ $stats['created'] }}</p>
            @else
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-10 text-center">
                    <p class="font-semibold text-slate-950">Рахунків поки немає</p>
                    <p class="mt-2 text-sm text-slate-500">Створені та оплачені рахунки зʼявляться тут.</p>
                    <a href="{{ route('account.payments.create') }}" class="mt-4 inline-flex rounded-full bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Створити рахунок</a>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-5 text-lg font-semibold text-slate-950">Баланси</h2>
            @if($balanceRows->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                    <span class="mx-auto mb-4 flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <x-icon name="wallet" class="h-5 w-5" />
                    </span>
                    <p class="font-semibold text-slate-950">Балансів поки немає</p>
                    <p class="mx-auto mt-2 max-w-64 text-sm leading-6 text-slate-500">Надходження після оплат будуть показані тут.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($balanceRows as $row)
                    <div class="flex items-center gap-2.5 rounded-2xl border border-slate-100 bg-slate-50/70 px-3 py-3">
                        <x-coin-icon :code="$row['code']" class="h-9 w-9 shrink-0" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold leading-5 text-slate-950">{{ $row['code'] }}</p>
                            <p class="mt-0.5 truncate text-xs font-medium leading-4 text-slate-400">
                                {{ $row['name'] ?: $row['network'] }}
                            </p>
                        </div>
                        <p class="shrink-0 whitespace-nowrap text-right font-mono text-sm font-semibold text-slate-950">{{ $row['amount'] }}</p>
                    </div>
                    @endforeach
                </div>
            @endif
            <a href="{{ route('account.balance') }}" class="mt-7 flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-blue-600 hover:bg-blue-50">
                Перейти до балансу
                <x-icon name="arrow-right" class="h-4 w-4" />
            </a>
        </div>
    </div>
</div>
@endsection
