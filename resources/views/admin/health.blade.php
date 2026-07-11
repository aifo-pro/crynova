@extends('layouts.app')
@section('title', 'Стан системи — Адмін')

@section('content')
@php
    $badge = fn (bool $ok) => $ok
        ? '<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">OK</span>'
        : '<span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-black text-rose-700 ring-1 ring-rose-200">ERROR</span>';
@endphp
<div class="mx-auto w-full max-w-5xl space-y-6">
    <div>
        <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
            <x-icon name="shield-check" class="h-3.5 w-3.5" /> Моніторинг
        </div>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950">Стан системи</h1>
        <p class="mt-2 text-sm text-slate-500">Технічний огляд платформи: база даних, черги, розклад завдань і платіжні активи.</p>
    </div>

    {{-- System --}}
    <x-card title="Система">
        <dl class="divide-y divide-slate-100">
            <div class="flex items-center justify-between py-3">
                <dt class="text-sm font-semibold text-slate-500">База даних</dt>
                <dd>{!! $badge($system['db']) !!}</dd>
            </div>
            @foreach([
                'PHP' => $system['php'], 'Laravel' => $system['laravel'], 'Середовище' => $system['environment'],
                'Cache' => $system['cache'], 'Queue' => $system['queue'], 'Session' => $system['session'],
            ] as $label => $value)
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm font-semibold text-slate-500">{{ $label }}</dt>
                    <dd class="font-mono text-sm font-bold text-slate-900">{{ $value }}</dd>
                </div>
            @endforeach
            <div class="flex items-center justify-between py-3">
                <dt class="text-sm font-semibold text-slate-500">Debug-режим</dt>
                <dd>
                    @if($system['debug'])
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-700 ring-1 ring-amber-200">УВІМКНЕНО</span>
                    @else
                        {!! $badge(true) !!}
                    @endif
                </dd>
            </div>
        </dl>
        @if($system['debug'] && $system['environment'] === 'production')
            <p class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-700">
                ⚠ Debug увімкнено в production — вимкніть APP_DEBUG=false.
            </p>
        @endif
    </x-card>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Invoices --}}
        <x-card title="Рахунки">
            <dl class="divide-y divide-slate-100">
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm font-semibold text-slate-500">В очікуванні</dt>
                    <dd class="text-lg font-black text-slate-900">{{ number_format($invoices['pending']) }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm font-semibold text-slate-500">Завислі (>30 хв)</dt>
                    <dd class="text-lg font-black {{ $invoices['stuck'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ number_format($invoices['stuck']) }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm font-semibold text-slate-500">Остання оплата</dt>
                    <dd class="text-sm font-bold text-slate-900">{{ $invoices['last_paid'] ? \Illuminate\Support\Carbon::parse($invoices['last_paid'])->diffForHumans() : '—' }}</dd>
                </div>
            </dl>
        </x-card>

        {{-- Webhooks --}}
        <x-card title="Webhook черга">
            <dl class="divide-y divide-slate-100">
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm font-semibold text-slate-500">Очікують повтору</dt>
                    <dd class="text-lg font-black text-slate-900">{{ number_format($webhooks['pending_retries']) }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm font-semibold text-slate-500">Вичерпали спроби</dt>
                    <dd class="text-lg font-black {{ $webhooks['dead'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ number_format($webhooks['dead']) }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm font-semibold text-slate-500">Найстаріший у черзі</dt>
                    <dd class="text-sm font-bold text-slate-900">{{ $webhooks['oldest_retry'] ? \Illuminate\Support\Carbon::parse($webhooks['oldest_retry'])->diffForHumans() : '—' }}</dd>
                </div>
            </dl>
        </x-card>
    </div>

    {{-- Scheduler --}}
    <x-card title="Заплановані завдання" subtitle="Потребують активного cron / schedule:run на сервері">
        <div class="divide-y divide-slate-100">
            @foreach($schedule as [$cmd, $freq])
                <div class="flex items-center justify-between py-3">
                    <span class="font-mono text-sm font-bold text-slate-800">{{ $cmd }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $freq }}</span>
                </div>
            @endforeach
        </div>
    </x-card>

    {{-- Currencies --}}
    <x-card title="Активні валюти" :subtitle="$currencies->count() . ' активних'">
        <div class="flex flex-wrap gap-2">
            @foreach($currencies as $cur)
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-black text-slate-700">
                    <x-coin-icon :code="$cur->code" class="h-5 w-5" />
                    {{ $cur->code }}
                    <span class="font-semibold text-slate-400">{{ $cur->network }}</span>
                </span>
            @endforeach
        </div>
    </x-card>
</div>
@endsection
