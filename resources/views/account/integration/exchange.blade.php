@extends('layouts.app')
@section('title', __('account.exchange.title'))

@section('content')
@php
    $netMeta = [
        'TRC20' => ['T', 'bg-red-500'], 'ERC20' => ['E', 'bg-indigo-500'],
        'BEP20' => ['B', 'bg-yellow-500'], 'BSC' => ['B', 'bg-yellow-500'],
    ];
    $iconFor = function ($code) {
        $base = strtolower(preg_replace('/[^A-Za-z].*$/', '', explode('_', $code)[0]));
        return in_array($base, ['btc','eth','usdt','trx','ltc','doge'], true)
            ? asset('assets/crynova/crypto-icons/'.$base.'.svg') : null;
    };
    $badgeFor = function ($code) use ($netMeta) {
        $net = \Illuminate\Support\Str::after($code, '_');
        $net = $net === $code ? '' : strtoupper($net);
        return $net === '' ? null : ($netMeta[$net] ?? [substr($net, 0, 1), 'bg-slate-400']);
    };
    $cur = $currencies->map(fn($c) => [
        'id' => $c->id, 'code' => $c->code, 'usd' => $prices[$c->code] ?? null,
        'icon' => $iconFor($c->code), 'letter' => strtoupper(substr($c->code, 0, 1)),
        'badge' => $badgeFor($c->code),
    ])->values();
@endphp
<div class="space-y-6"
     x-data="{
        prices: @js($cur),
        from: {{ $currencies->firstWhere('code','BTC')?->id ?? ($currencies->first()->id ?? 'null') }},
        to: {{ $currencies->firstWhere('code','USDT_TRC20')?->id ?? ($currencies->skip(1)->first()->id ?? 'null') }},
        amount: '',
        fee: 0.5,
        coinOf(id){ return this.prices.find(p=>p.id==id) || null; },
        priceOf(id){ const c = this.coinOf(id); return c ? c.usd : null; },
        codeOf(id){ const c = this.coinOf(id); return c ? c.code : ''; },
        fromOpen:false, toOpen:false,
        get rate(){ const pf=this.priceOf(this.from), pt=this.priceOf(this.to); return (pf&&pt) ? pf/pt : null; },
        get gross(){ const r=this.rate; return (r && this.amount>0) ? this.amount*r : 0; },
        get net(){ return this.gross * (1 - this.fee/100); },
        swap(){ const t=this.from; this.from=this.to; this.to=t; },
        fmt(n){ if(!n) return '0'; return Number(n).toLocaleString('en-US',{maximumFractionDigits:8}); },
     }">
    <h1 class="text-2xl font-semibold text-slate-950">{{ __('account.exchange.title') }}</h1>
    @if($projects->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
            <p class="text-lg font-semibold text-slate-950">{{ __('account.exchange.no_projects') }}</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ __('account.exchange.no_projects_text') }}</p>
            <x-button href="{{ route('account.projects') }}" class="mt-5">{{ __('account.exchange.to_projects') }}</x-button>
        </div>
    @else
    <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
        {{-- Exchange form --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-2"><x-icon name="coins" class="h-5 w-5 text-blue-600" /><h2 class="font-semibold text-slate-950">{{ __('account.exchange.heading') }}</h2></div>

            <form method="POST" action="{{ route('account.exchange.execute') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="from_currency_id" :value="from">
                <input type="hidden" name="to_currency_id" :value="to">

                <div>
                    <label class="fin-label">{{ __('account.exchange.project') }}</label>
                    <x-project-select name="merchant_id" :projects="$projects" required />
                </div>

                {{-- From --}}
                <div class="rounded-2xl border border-slate-200 p-4">
                    <label class="fin-label">{{ __('account.exchange.you_give') }}</label>
                    <div class="flex gap-2">
                        <input name="amount" x-model="amount" type="number" step="any" min="0" required class="fin-input flex-1" placeholder="0.00">
                        <div class="relative w-40 shrink-0" @keydown.escape="fromOpen=false">
                            <button type="button" @click="fromOpen=!fromOpen" class="fin-input flex w-full items-center justify-between gap-2 pr-3">
                                <span class="flex min-w-0 items-center gap-2">
                                    <template x-if="coinOf(from)">
                                        <span class="relative inline-flex h-6 w-6 shrink-0">
                                            <template x-if="coinOf(from).icon"><img :src="coinOf(from).icon" class="h-6 w-6 rounded-full"></template>
                                            <template x-if="!coinOf(from).icon"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-600" x-text="coinOf(from).letter"></span></template>
                                            <template x-if="coinOf(from).badge"><span class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3 items-center justify-center rounded-full text-[7px] font-black leading-none text-white ring-2 ring-white" :class="coinOf(from).badge[1]" x-text="coinOf(from).badge[0]"></span></template>
                                        </span>
                                    </template>
                                    <span class="truncate font-semibold text-slate-900" x-text="codeOf(from)"></span>
                                </span>
                                <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="fromOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                            </button>
                            <div x-show="fromOpen" x-cloak @click.outside="fromOpen=false" x-transition.opacity class="absolute right-0 z-30 mt-1 max-h-64 w-56 overflow-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
                                <template x-for="c in prices" :key="c.id">
                                    <button type="button" @click="from=c.id; fromOpen=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left transition hover:bg-slate-50" :class="from==c.id ? 'bg-blue-50' : ''">
                                        <span class="relative inline-flex h-6 w-6 shrink-0">
                                            <template x-if="c.icon"><img :src="c.icon" class="h-6 w-6 rounded-full"></template>
                                            <template x-if="!c.icon"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-600" x-text="c.letter"></span></template>
                                            <template x-if="c.badge"><span class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3 items-center justify-center rounded-full text-[7px] font-black leading-none text-white ring-2 ring-white" :class="c.badge[1]" x-text="c.badge[0]"></span></template>
                                        </span>
                                        <span class="truncate font-semibold text-slate-900" x-text="c.code"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Swap --}}
                <div class="flex justify-center">
                    <button type="button" @click="swap()" class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-blue-600 hover:bg-blue-50">⇅</button>
                </div>

                {{-- To --}}
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <label class="fin-label">{{ __('account.exchange.you_get') }}</label>
                    <div class="flex gap-2">
                        <input type="text" readonly class="fin-input flex-1 bg-white" :value="fmt(net)">
                        <div class="relative w-40 shrink-0" @keydown.escape="toOpen=false">
                            <button type="button" @click="toOpen=!toOpen" class="fin-input flex w-full items-center justify-between gap-2 pr-3 bg-white">
                                <span class="flex min-w-0 items-center gap-2">
                                    <template x-if="coinOf(to)">
                                        <span class="relative inline-flex h-6 w-6 shrink-0">
                                            <template x-if="coinOf(to).icon"><img :src="coinOf(to).icon" class="h-6 w-6 rounded-full"></template>
                                            <template x-if="!coinOf(to).icon"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-600" x-text="coinOf(to).letter"></span></template>
                                            <template x-if="coinOf(to).badge"><span class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3 items-center justify-center rounded-full text-[7px] font-black leading-none text-white ring-2 ring-white" :class="coinOf(to).badge[1]" x-text="coinOf(to).badge[0]"></span></template>
                                        </span>
                                    </template>
                                    <span class="truncate font-semibold text-slate-900" x-text="codeOf(to)"></span>
                                </span>
                                <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="toOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                            </button>
                            <div x-show="toOpen" x-cloak @click.outside="toOpen=false" x-transition.opacity class="absolute right-0 z-30 mt-1 max-h-64 w-56 overflow-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
                                <template x-for="c in prices" :key="c.id">
                                    <button type="button" @click="to=c.id; toOpen=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left transition hover:bg-slate-50" :class="to==c.id ? 'bg-blue-50' : ''">
                                        <span class="relative inline-flex h-6 w-6 shrink-0">
                                            <template x-if="c.icon"><img :src="c.icon" class="h-6 w-6 rounded-full"></template>
                                            <template x-if="!c.icon"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-600" x-text="c.letter"></span></template>
                                            <template x-if="c.badge"><span class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3 items-center justify-center rounded-full text-[7px] font-black leading-none text-white ring-2 ring-white" :class="c.badge[1]" x-text="c.badge[0]"></span></template>
                                        </span>
                                        <span class="truncate font-semibold text-slate-900" x-text="c.code"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl bg-slate-50 p-3 text-sm text-slate-500">
                    <div class="flex justify-between"><span>{{ __('account.exchange.rate') }}</span><span class="font-mono text-slate-700"><template x-if="rate">1 <span x-text="codeOf(from)"></span> ≈ <span x-text="fmt(rate)"></span> <span x-text="codeOf(to)"></span></template><template x-if="!rate">{{ __('account.exchange.unavailable') }}</template></span></div>
                    <div class="mt-1 flex justify-between"><span>{{ __('account.exchange.fee') }}</span><span class="text-slate-700" x-text="fee + '%'"></span></div>
                </div>

                <x-button type="submit" icon="coins" class="w-full rounded-full">{{ __('account.exchange.exchange_btn') }}</x-button>
            </form>
        </div>

        {{-- Live rates --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-950">{{ __('account.exchange.rates_usd') }}</h2>
            <p class="mb-3 text-xs text-slate-400">{{ __('account.exchange.rates_source') }}</p>
            <div class="space-y-1.5">
                @foreach($cur as $c)
                <div class="flex items-center justify-between rounded-xl px-3 py-2 hover:bg-slate-50">
                    <span class="flex items-center gap-2 text-sm font-medium text-slate-800">
                        <x-coin-icon :code="$c['code']" class="h-6 w-6" />
                        {{ $c['code'] }}
                    </span>
                    <span class="font-mono text-sm {{ $c['usd'] ? 'text-slate-700' : 'text-slate-300' }}">
                        {{ $c['usd'] ? '$ '.rtrim(rtrim(number_format($c['usd'],8,'.',''),'0'),'.') : '—' }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
