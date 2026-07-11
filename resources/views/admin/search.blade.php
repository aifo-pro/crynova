@extends('layouts.app')
@section('title', 'Пошук — Адмін')

@section('content')
<div class="mx-auto w-full max-w-5xl space-y-6">
    <div>
        <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
            <x-icon name="search" class="h-3.5 w-3.5" /> Глобальний пошук
        </div>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950">Результати пошуку</h1>
    </div>

    <form method="GET" action="{{ route('admin.search') }}" class="relative">
        <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input name="q" value="{{ $q }}" autofocus
               class="fin-input min-h-14 w-full rounded-2xl pl-11"
               placeholder="UUID, order ID, email, домен, адреса гаманця, tx hash...">
    </form>

    @if(mb_strlen($q) < 2)
        <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500 shadow-sm">
            Введіть щонайменше 2 символи для пошуку.
        </div>
    @elseif($total === 0)
        <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-500">
                <x-icon name="search" class="h-6 w-6" />
            </div>
            <p class="mt-4 text-base font-black text-slate-950">Нічого не знайдено</p>
            <p class="mt-1 text-sm text-slate-500">За запитом «{{ $q }}» збігів немає.</p>
        </div>
    @else
        <p class="text-sm text-slate-500">Знайдено {{ $total }} збігів за запитом «<span class="font-bold text-slate-800">{{ $q }}</span>».</p>

        {{-- Merchants --}}
        @if($merchants->isNotEmpty())
            <x-card title="Мерчанти" :subtitle="$merchants->count() . ' збігів'">
                <div class="divide-y divide-slate-100">
                    @foreach($merchants as $m)
                        <a href="{{ route('admin.merchants.show', $m) }}" class="group flex items-center gap-3 py-3 transition hover:bg-blue-50/40">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-blue-600 text-sm font-black text-white">{{ mb_strtoupper(mb_substr($m->name ?: 'M', 0, 1)) }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-black text-slate-950 group-hover:text-blue-700">{{ $m->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $m->user?->email ?? '—' }} · {{ $m->domain ?: $m->website ?: '—' }}</p>
                            </div>
                            <x-icon name="arrow-right" class="h-4 w-4 shrink-0 text-slate-400" />
                        </a>
                    @endforeach
                </div>
            </x-card>
        @endif

        {{-- Invoices --}}
        @if($invoices->isNotEmpty())
            <x-card title="Рахунки" :subtitle="$invoices->count() . ' збігів'">
                <div class="divide-y divide-slate-100">
                    @foreach($invoices as $inv)
                        <a href="{{ route('admin.invoices.show', $inv) }}" class="group flex items-center gap-3 py-3 transition hover:bg-blue-50/40">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-blue-50 text-blue-700"><x-icon name="file-text" class="h-4 w-4" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-mono text-sm font-black text-blue-600 group-hover:text-blue-700">#{{ \Illuminate\Support\Str::of($inv->uuid)->explode('-')->first() }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $inv->merchant?->name ?? '—' }} · {{ $inv->order_id ? 'Order '.$inv->order_id.' · ' : '' }}{{ optional($inv->currency)->code ?? $inv->price_currency }}</p>
                            </div>
                            <x-status-badge :status="$inv->status" />
                        </a>
                    @endforeach
                </div>
            </x-card>
        @endif

        {{-- Transactions --}}
        @if($transactions->isNotEmpty())
            <x-card title="Транзакції" :subtitle="$transactions->count() . ' збігів'">
                <div class="divide-y divide-slate-100">
                    @foreach($transactions as $tx)
                        <a href="{{ $tx->invoice ? route('admin.invoices.show', $tx->invoice) : '#' }}" class="group flex items-center gap-3 py-3 transition hover:bg-blue-50/40">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-slate-100 text-slate-500"><x-icon name="link" class="h-4 w-4" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-mono text-xs font-bold text-slate-800">{{ $tx->tx_hash }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $tx->amount }} · {{ $tx->status }}</p>
                            </div>
                            <x-icon name="arrow-right" class="h-4 w-4 shrink-0 text-slate-400" />
                        </a>
                    @endforeach
                </div>
            </x-card>
        @endif

        {{-- Users --}}
        @if($users->isNotEmpty())
            <x-card title="Користувачі" :subtitle="$users->count() . ' збігів'">
                <div class="divide-y divide-slate-100">
                    @foreach($users as $u)
                        <a href="{{ route('admin.users.edit', $u) }}" class="group flex items-center gap-3 py-3 transition hover:bg-blue-50/40">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-slate-100 text-slate-600"><x-icon name="user" class="h-4 w-4" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-black text-slate-950 group-hover:text-blue-700">{{ $u->name ?: '—' }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $u->email }} · ID #{{ $u->id }}</p>
                            </div>
                            <x-icon name="arrow-right" class="h-4 w-4 shrink-0 text-slate-400" />
                        </a>
                    @endforeach
                </div>
            </x-card>
        @endif
    @endif
</div>
@endsection
