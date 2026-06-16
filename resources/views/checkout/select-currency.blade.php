<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('checkout.select.title') }} - Crynova</title>
    <link rel="icon" href="{{ asset('assets/crynova/favicon/favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('assets/crynova/favicon/apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $priceAmount = rtrim(rtrim((string) $invoice->price_amount, '0'), '.') ?: '0';
    $iconFor = function ($code) {
        $base = strtolower(explode('_', $code)[0]);
        return in_array($base, ['btc','eth','usdt','trx','ltc','doge'], true)
            ? asset('assets/crynova/crypto-icons/'.$base.'.svg') : null;
    };
    $opts = collect($options)->map(fn ($o) => $o + ['icon' => $iconFor($o['code'])])->values();
    $bases = $opts->groupBy('base')->map(fn ($g) => $g->first())->values();
    $expiresLeft = $invoice->expires_at ? max(0, (int) now()->diffInSeconds($invoice->expires_at, false)) : null;
@endphp
<body class="min-h-screen bg-[#f7f8fb] text-slate-950 antialiased">
<main class="mx-auto flex min-h-screen max-w-lg flex-col px-4 py-8"
      x-data="{
        opts: @js($opts),
        bases: @js($bases),
        base: @js($opts->first()['base'] ?? ''),
        code: @js($opts->first()['code'] ?? ''),
        modal: false, curOpen: false, netOpen: false,
        get current() { return this.opts.find(o => o.code === this.code) || null; },
        get networks() { return this.opts.filter(o => o.base === this.base); },
        pickBase(b) { this.base = b; var f = this.opts.find(o => o.base === b); this.code = f ? f.code : this.code; this.curOpen = false; },
        pickNet(c) { this.code = c; this.netOpen = false; },
      }">

    <header class="mb-5 flex items-center justify-between gap-3">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5">
            <x-logo variant="mark" class="h-9 w-9 rounded-xl shadow-md shadow-blue-600/20" />
            <span class="text-base font-black tracking-tight text-slate-900">Crynova</span>
        </a>
        <div class="flex items-center gap-3">
            @if($expiresLeft !== null)
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-600"
                      x-data="{ s: {{ $expiresLeft }} }" x-init="setInterval(() => { if (s>0) s-- }, 1000)">
                    <x-icon name="clock" class="h-3.5 w-3.5" />
                    <span x-text="String(Math.floor(s/3600)).padStart(2,'0')+':'+String(Math.floor(s%3600/60)).padStart(2,'0')+':'+String(s%60).padStart(2,'0')"></span>
                </span>
            @endif
            <x-language-switcher compact />
        </div>
    </header>

    @if($opts->isEmpty())
        <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-400 shadow-sm">{{ __('checkout.select.unavailable') }}</div>
    @else
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60">
        <div class="flex items-center justify-between text-sm">
            <span class="font-bold text-slate-900">{{ $invoice->order_id ?: ('#'.substr($invoice->uuid,0,8)) }}</span>
            <span class="inline-flex items-center gap-1.5 font-semibold text-slate-500"><x-icon name="wallet" class="h-4 w-4" /> {{ $invoice->merchant->name }}</span>
        </div>

        <div class="mt-4 flex items-center justify-between">
            <span class="text-sm font-semibold text-slate-500">{{ __('checkout.select.to_pay') }}</span>
            <span class="flex items-center gap-2">
                <button type="button" @click="modal = true" class="grid h-5 w-5 place-items-center rounded-full bg-slate-100 text-xs font-bold text-slate-400 transition hover:bg-blue-100 hover:text-blue-600">?</button>
                <span class="text-lg font-black text-slate-950"><span x-text="current ? current.total : ''"></span> <span class="text-blue-600" x-text="current ? current.base : ''"></span></span>
            </span>
        </div>

        <div class="mt-6">
            <label class="mb-1.5 block text-sm font-semibold text-slate-600">{{ __('checkout.select.choose_currency') }}</label>
            <div class="relative" @keydown.escape="curOpen=false">
                <button type="button" @click="curOpen=!curOpen" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 text-left transition hover:border-blue-300">
                    <span class="flex items-center gap-2.5">
                        <template x-for="b in bases" :key="b.base">
                            <span x-show="base===b.base" class="flex items-center gap-2.5">
                                <template x-if="b.icon"><img :src="b.icon" class="h-7 w-7 rounded-full"></template>
                                <span class="font-bold text-slate-900" x-text="b.base"></span>
                            </span>
                        </template>
                    </span>
                    <svg class="h-4 w-4 text-slate-400 transition" :class="curOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="curOpen" x-cloak @click.outside="curOpen=false" x-transition.opacity class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-2xl border border-slate-200 bg-white p-1 shadow-xl">
                    <template x-for="b in bases" :key="b.base">
                        <button type="button" @click="pickBase(b.base)" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left transition hover:bg-slate-50" :class="base===b.base ? 'bg-blue-50' : ''">
                            <template x-if="b.icon"><img :src="b.icon" class="h-7 w-7 rounded-full"></template>
                            <span class="flex-1">
                                <span class="block font-bold text-slate-900" x-text="b.base"></span>
                                <span class="block text-xs text-slate-400" x-text="b.name"></span>
                            </span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <div class="mb-1.5 flex items-center justify-between gap-2">
                <label class="block text-sm font-semibold text-slate-600">{{ __('checkout.select.choose_network') }}</label>
                <span class="inline-flex items-center gap-1 text-xs text-slate-400"><x-icon name="clock" class="h-3.5 w-3.5" /> {{ __('checkout.select.network_time') }}</span>
            </div>
            <div class="relative" @keydown.escape="netOpen=false">
                <button type="button" @click="netOpen=!netOpen" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 text-left transition hover:border-blue-300">
                    <span class="flex items-center gap-2.5">
                        <template x-if="current && current.net_icon"><img :src="current.net_icon" class="h-6 w-6 rounded-full"></template>
                        <template x-if="current && !current.net_icon"><span class="grid h-6 w-6 place-items-center rounded-full bg-slate-200 text-[10px] font-black text-slate-600" x-text="current ? current.net_letter : ''"></span></template>
                        <span class="font-semibold text-slate-900" x-text="current ? current.network_label : ''"></span>
                    </span>
                    <svg class="h-4 w-4 text-slate-400 transition" :class="netOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="netOpen" x-cloak @click.outside="netOpen=false" x-transition.opacity class="absolute z-20 mt-1 w-full overflow-auto rounded-2xl border border-slate-200 bg-white p-1 shadow-xl">
                    <template x-for="o in networks" :key="o.code">
                        <button type="button" @click="pickNet(o.code)" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left transition hover:bg-slate-50" :class="code===o.code ? 'bg-blue-50' : ''">
                            <span class="flex items-center gap-2.5">
                                <template x-if="o.net_icon"><img :src="o.net_icon" class="h-6 w-6 rounded-full"></template>
                                <template x-if="!o.net_icon"><span class="grid h-6 w-6 place-items-center rounded-full bg-slate-200 text-[10px] font-black text-slate-600" x-text="o.net_letter"></span></template>
                                <span class="font-semibold text-slate-900" x-text="o.network_label"></span>
                            </span>
                            <span class="font-mono text-xs text-slate-400">≈ <span x-text="o.total"></span> <span x-text="o.base"></span></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('checkout.select-currency', $invoice->uuid) }}" class="mt-6">
            @csrf
            <input type="hidden" name="currency" :value="code">
            <button type="submit" class="w-full rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition hover:opacity-90">
                {{ __('checkout.select.proceed') }}
            </button>
        </form>

        <div class="mt-4 flex items-center justify-between text-xs text-slate-400"><span>{{ __('checkout.select.powered') }} <span class="font-bold text-slate-500">Crynova</span></span><a href="{{ url('/tos') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-semibold text-slate-500 hover:text-blue-600"><x-icon name="book" class="h-3.5 w-3.5" /> {{ __('checkout.select.terms') }}</a></div>
    </div>

    {{-- Fee breakdown modal --}}
    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-slate-900/40" @click="modal=false"></div>
        <div x-show="modal" x-transition class="relative w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-950">{{ __('checkout.select.m_title') }}</h2>
                <button type="button" @click="modal=false" class="grid h-8 w-8 place-items-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200"><x-icon name="x" class="h-4 w-4" /></button>
            </div>
            <div class="mt-5 grid grid-cols-2 gap-x-6 gap-y-5 text-sm">
                <div>
                    <p class="text-xs text-slate-400">{{ __('checkout.select.m_currency') }}</p>
                    <p class="mt-1 flex items-center gap-2 font-bold text-slate-900"><template x-if="current && current.icon"><img :src="current.icon" class="h-5 w-5 rounded-full"></template><span x-text="current ? current.name : ''"></span></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400">{{ __('checkout.select.m_network') }}</p>
                    <p class="mt-1 font-bold text-slate-900" x-text="current ? current.network_label : ''"></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400">{{ __('checkout.select.m_invoice_amount') }}</p>
                    <p class="mt-1 font-bold text-slate-900"><span x-text="current ? current.amount : ''"></span> <span x-text="current ? current.base : ''"></span></p>
                    <p class="text-xs text-slate-400">{{ $priceAmount }} {{ $invoice->price_currency }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400">{{ __('checkout.select.m_service_fee') }}</p>
                    <p class="mt-1 font-bold text-slate-900">0 <span x-text="current ? current.base : ''"></span></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400">{{ __('checkout.select.m_received') }}</p>
                    <p class="mt-1 font-bold text-slate-900">0 <span x-text="current ? current.base : ''"></span></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400">{{ __('checkout.select.m_transfer_fee') }}</p>
                    <p class="mt-1 font-bold text-amber-600"><span x-text="current ? current.fee : ''"></span> <span x-text="current ? current.base : ''"></span></p>
                </div>
                <div class="col-span-2 flex items-center justify-between border-t border-slate-100 pt-3">
                    <p class="text-sm font-bold text-slate-700">{{ __('checkout.select.m_total') }}</p>
                    <p class="text-base font-black text-blue-700"><span x-text="current ? current.total : ''"></span> <span x-text="current ? current.base : ''"></span></p>
                </div>
            </div>
            <p class="mt-5 flex items-start gap-2 rounded-xl bg-slate-50 p-3 text-xs leading-5 text-slate-500">
                <x-icon name="alert-triangle" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-500" />
                {{ __('checkout.select.transfer_note') }}
            </p>
        </div>
    </div>
    @endif
</main>
</body>
</html>
