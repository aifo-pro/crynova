@extends('layouts.app')
@section('title', 'Рахунок ' . substr($invoice->uuid, 0, 8))

@section('content')
@php
    $statusLabels = [
        'pending' => 'Очікує оплату',
        'waiting_confirmations' => 'Очікує підтвердження',
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

    $currencyCode = $invoice->currency?->code ?? 'CRYPTO';
    $statusLabel = $statusLabels[$invoice->status] ?? ucfirst(str_replace('_', ' ', $invoice->status));
    $statusClass = $statusClasses[$invoice->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
    $checkoutUrl = route('checkout.show', $invoice->uuid);
    $merchantUrl = $invoice->merchant ? route('admin.merchants.show', $invoice->merchant) : null;

    $formatAmount = function ($value): string {
        $value = (string) ($value ?? '0');
        $value = str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;

        return $value === '' ? '0' : $value;
    };

    $formatDate = fn ($date) => $date ? $date->format('d.m.Y H:i') : '-';

    $detailItems = [
        ['Мерчант', $invoice->merchant?->name ?? '-', 'landmark', false],
        ['Валюта', $currencyCode, 'coins', false],
        ['Мережа', $invoice->currency?->network ?? '-', 'link', false],
        ['Order ID', $invoice->order_id ?: '-', 'file-text', false],
        ['Адреса оплати', $invoice->pay_address ?: '-', 'wallet', true],
        ['Memo / Tag', $invoice->pay_memo ?: '-', 'key', true],
        ['Діє до', $formatDate($invoice->expires_at), 'clock', false],
        ['Оплачено', $formatDate($invoice->paid_at), 'check', false],
        ['Комісія', $formatAmount($invoice->fee_amount) . ' ' . $currencyCode . ' / ' . $formatAmount($invoice->fee_percent) . '%', 'banknote', false],
        ['До зарахування', $formatAmount($invoice->net_amount) . ' ' . $currencyCode, 'wallet', false],
    ];
@endphp

<div class="mx-auto w-full max-w-7xl space-y-8">
    <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center gap-2 font-bold text-slate-500 transition hover:text-blue-700">
                    <x-icon name="arrow-left" class="h-4 w-4" />
                    Рахунки
                </a>
                <span class="text-slate-300">/</span>
                <span class="max-w-full break-all font-mono text-blue-600">{{ $invoice->uuid }}</span>
            </div>
            <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-600/20">
                    <x-icon name="file-text" class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <h1 class="text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Деталі рахунку</h1>
                    <p class="mt-1 break-all text-sm text-slate-500">Створено {{ $formatDate($invoice->created_at) }}</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center gap-2 whitespace-nowrap rounded-full px-4 py-2 text-sm font-black ring-1 {{ $statusClass }}">
                <span class="h-2 w-2 rounded-full bg-current"></span>
                {{ $statusLabel }}
            </span>
            <button type="button" data-copy-text="{{ $invoice->uuid }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600 shadow-sm transition hover:border-blue-200 hover:text-blue-700">
                <x-icon name="copy" class="h-4 w-4" />
                UUID
            </button>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50/80 px-6 py-6">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Сума до оплати</p>
                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                <p class="break-all font-mono text-4xl font-black tracking-tight text-slate-950">{{ $formatAmount($invoice->amount) }}</p>
                                <span class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-2 text-sm font-black text-blue-700">
                                    <x-coin-icon :code="$currencyCode" class="h-7 w-7" />
                                    {{ $currencyCode }}
                                </span>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-5 py-4 text-right">
                            <p class="text-xs font-black uppercase tracking-[0.14em] text-emerald-600">Отримано</p>
                            <p class="mt-2 break-all font-mono text-2xl font-black text-emerald-700">{{ $formatAmount($invoice->amount_received) }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 p-6 md:grid-cols-2">
                    @foreach($detailItems as [$label, $value, $icon, $copyable])
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="flex items-start gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-50 text-slate-500">
                                    <x-icon name="{{ $icon }}" class="h-4 w-4" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">{{ $label }}</dt>
                                    <dd class="mt-2 break-all font-mono text-sm font-bold leading-6 text-slate-950">{{ $value }}</dd>
                                </div>
                                @if(($copyable ?? false) && $value !== '-')
                                    <button type="button" data-copy-text="{{ $value }}" class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-slate-200 text-slate-400 transition hover:border-blue-200 hover:text-blue-700">
                                        <x-icon name="copy" class="h-4 w-4" />
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-2 border-b border-slate-100 bg-slate-50/80 px-6 py-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-black tracking-tight text-slate-950">Блокчейн-транзакції</h2>
                        <p class="mt-1 text-sm text-slate-500">Знайдено {{ $invoice->transactions->count() }} транзакцій для цього рахунку.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $invoice->transactions->count() }}</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($invoice->transactions as $tx)
                        @php
                            $txStatusLabel = $statusLabels[$tx->status] ?? ucfirst(str_replace('_', ' ', $tx->status));
                            $txStatusClass = $statusClasses[$tx->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
                        @endphp
                        <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.6fr)_auto_auto] lg:items-center lg:gap-6">
                            {{-- TX hash --}}
                            <div class="flex min-w-0 items-center gap-2 sm:col-span-2 lg:col-span-1">
                                <span class="min-w-0 truncate font-mono text-sm font-bold text-blue-600" title="{{ $tx->tx_hash }}">{{ $tx->tx_hash ?: '-' }}</span>
                                @if($tx->tx_hash)
                                    <button type="button" data-copy-text="{{ $tx->tx_hash }}" class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-slate-200 text-slate-400 transition hover:border-blue-200 hover:text-blue-700">
                                        <x-icon name="copy" class="h-3.5 w-3.5" />
                                    </button>
                                @endif
                            </div>
                            {{-- Amount + confirmations --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black uppercase tracking-[0.12em] text-slate-400">Сума</p>
                                    <p class="mt-1 truncate font-mono text-sm font-black text-slate-950" title="{{ $formatAmount($tx->amount) }} {{ $currencyCode }}">{{ $formatAmount($tx->amount) }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.12em] text-slate-400">Підтвердж.</p>
                                    <p class="mt-1 font-mono text-sm font-black {{ $tx->isConfirmed() ? 'text-emerald-700' : 'text-amber-700' }}">{{ $tx->confirmations }} / {{ $tx->confirmations_required }}</p>
                                </div>
                            </div>
                            {{-- Status + time --}}
                            <div class="flex items-center justify-between gap-3 lg:flex-col lg:items-end">
                                <span class="inline-flex items-center gap-2 whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $txStatusClass }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ $txStatusLabel }}
                                </span>
                                <span class="whitespace-nowrap text-xs text-slate-400">{{ $formatDate($tx->created_at) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-14 text-center">
                            <div class="mx-auto max-w-sm">
                                <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-500">
                                    <x-icon name="link" class="h-5 w-5" />
                                </div>
                                <p class="mt-4 font-black text-slate-950">Транзакцій ще немає</p>
                                <p class="mt-1 text-sm text-slate-500">Коли мережа побачить оплату, записи з'являться тут.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50/80 px-6 py-6">
                    <h2 class="text-xl font-black tracking-tight text-slate-950">Доставка webhook</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $invoice->webhook_delivered ? 'Остання подія доставлена успішно.' : 'Очікує успішної доставки або ще не надсилалась.' }}
                    </p>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($invoice->webhookLogs as $log)
                        <div class="flex items-start justify-between gap-3 px-6 py-4">
                            <div class="min-w-0">
                                <p class="truncate font-mono text-xs font-bold text-slate-900" title="{{ $log->event }}">{{ $log->event }}</p>
                                @if($log->url)
                                    <p class="mt-1 truncate text-xs text-slate-500" title="{{ $log->url }}">{{ $log->url }}</p>
                                @endif
                                <p class="mt-1 text-xs text-slate-400">Спроба {{ $log->attempt }}</p>
                            </div>
                            @if($log->success)
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">
                                    <x-icon name="check" class="h-3.5 w-3.5" />
                                    {{ $log->http_status }}
                                </span>
                            @else
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-black text-rose-700 ring-1 ring-rose-200">
                                    <x-icon name="x" class="h-3.5 w-3.5" />
                                    {{ $log->http_status ?? 'err' }}
                                </span>
                            @endif
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center text-sm text-slate-500">Webhook ще не надсилались.</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[1.6rem] border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black tracking-tight text-slate-950">Швидкі посилання</h2>
                <div class="mt-5 space-y-3">
                    <a href="{{ $checkoutUrl }}" target="_blank" rel="noopener" class="flex min-h-14 items-center justify-between gap-3 rounded-2xl border border-blue-100 bg-blue-50 px-4 text-sm font-black text-blue-700 transition hover:border-blue-200 hover:bg-blue-100">
                        <span class="inline-flex min-w-0 items-center gap-3">
                            <x-icon name="qr" class="h-4 w-4 shrink-0" />
                            <span>Сторінка оплати</span>
                        </span>
                        <x-icon name="arrow-right" class="h-4 w-4 shrink-0" />
                    </a>
                    @if($merchantUrl)
                        <a href="{{ $merchantUrl }}" class="flex min-h-14 items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-black text-slate-700 transition hover:border-blue-200 hover:text-blue-700">
                            <span class="inline-flex min-w-0 items-center gap-3">
                                <x-icon name="landmark" class="h-4 w-4 shrink-0" />
                                <span>Профіль мерчанта</span>
                            </span>
                            <x-icon name="arrow-right" class="h-4 w-4 shrink-0" />
                        </a>
                    @endif
                    <button type="button" data-copy-text="{{ $checkoutUrl }}" class="flex min-h-14 w-full items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-black text-slate-700 transition hover:border-blue-200 hover:text-blue-700">
                        <span class="inline-flex min-w-0 items-center gap-3">
                            <x-icon name="copy" class="h-4 w-4 shrink-0" />
                            <span>Копіювати checkout URL</span>
                        </span>
                    </button>
                </div>
            </section>

            <section class="rounded-[1.6rem] border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black tracking-tight text-slate-950">Службові поля</h2>
                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Webhook спроб</p>
                        <p class="mt-2 font-mono text-lg font-black text-slate-950">{{ $invoice->webhook_attempts ?? 0 }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Остання відправка</p>
                        <p class="mt-2 break-all font-mono text-sm font-bold text-slate-950">{{ $formatDate($invoice->webhook_last_sent_at) }}</p>
                    </div>
                    @if($invoice->refund_address || $invoice->refund_tx_hash)
                        <div class="rounded-2xl border border-rose-100 bg-rose-50 p-4">
                            <p class="text-xs font-black uppercase tracking-[0.12em] text-rose-500">Refund</p>
                            <p class="mt-2 break-all font-mono text-sm font-bold text-rose-800">{{ $invoice->refund_address ?: '-' }}</p>
                            <p class="mt-2 break-all font-mono text-xs text-rose-700">{{ $invoice->refund_tx_hash ?: '-' }}</p>
                        </div>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
