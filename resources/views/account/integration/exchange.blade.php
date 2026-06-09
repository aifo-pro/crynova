@extends('layouts.app')
@section('title', 'Обмен')

@section('content')
@php
    $cur = $currencies->map(fn($c) => ['id'=>$c->id,'code'=>$c->code,'usd'=>$prices[$c->code] ?? null])->values();
@endphp
<div class="space-y-6"
     x-data="{
        prices: @js($cur),
        from: {{ $currencies->firstWhere('code','BTC')?->id ?? ($currencies->first()->id ?? 'null') }},
        to: {{ $currencies->firstWhere('code','USDT_TRC20')?->id ?? ($currencies->skip(1)->first()->id ?? 'null') }},
        amount: '',
        fee: 0.5,
        priceOf(id){ const c = this.prices.find(p=>p.id==id); return c ? c.usd : null; },
        codeOf(id){ const c = this.prices.find(p=>p.id==id); return c ? c.code : ''; },
        get rate(){ const pf=this.priceOf(this.from), pt=this.priceOf(this.to); return (pf&&pt) ? pf/pt : null; },
        get gross(){ const r=this.rate; return (r && this.amount>0) ? this.amount*r : 0; },
        get net(){ return this.gross * (1 - this.fee/100); },
        swap(){ const t=this.from; this.from=this.to; this.to=t; },
        fmt(n){ if(!n) return '0'; return Number(n).toLocaleString('en-US',{maximumFractionDigits:8}); },
     }">
    <h1 class="text-2xl font-semibold text-slate-950">Обмен</h1>
    @if($projects->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
            <p class="text-lg font-semibold text-slate-950">Немає активних проєктів</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">Обмен доступен между балансами одобренного проекта.</p>
            <x-button href="{{ route('account.projects') }}" class="mt-5">К проектам</x-button>
        </div>
    @else
    <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
        {{-- Exchange form --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-2"><x-icon name="coins" class="h-5 w-5 text-blue-600" /><h2 class="font-semibold text-slate-950">Обмен криптовалют</h2></div>

            <form method="POST" action="{{ route('account.exchange.execute') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="from_currency_id" :value="from">
                <input type="hidden" name="to_currency_id" :value="to">

                <div>
                    <label class="fin-label">Проект</label>
                    <select name="merchant_id" required class="fin-input">@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
                </div>

                {{-- From --}}
                <div class="rounded-2xl border border-slate-200 p-4">
                    <label class="fin-label">Віддаєте</label>
                    <div class="flex gap-2">
                        <input name="amount" x-model="amount" type="number" step="any" min="0" required class="fin-input flex-1" placeholder="0.00">
                        <select x-model="from" class="fin-input w-40">
                            <template x-for="c in prices" :key="c.id"><option :value="c.id" x-text="c.code"></option></template>
                        </select>
                    </div>
                </div>

                {{-- Swap --}}
                <div class="flex justify-center">
                    <button type="button" @click="swap()" class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-blue-600 hover:bg-blue-50">⇅</button>
                </div>

                {{-- To --}}
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <label class="fin-label">Получаете (примерно)</label>
                    <div class="flex gap-2">
                        <input type="text" readonly class="fin-input flex-1 bg-white" :value="fmt(net)">
                        <select x-model="to" class="fin-input w-40">
                            <template x-for="c in prices" :key="c.id"><option :value="c.id" x-text="c.code"></option></template>
                        </select>
                    </div>
                </div>

                <div class="rounded-xl bg-slate-50 p-3 text-sm text-slate-500">
                    <div class="flex justify-between"><span>Курс</span><span class="font-mono text-slate-700"><template x-if="rate">1 <span x-text="codeOf(from)"></span> ≈ <span x-text="fmt(rate)"></span> <span x-text="codeOf(to)"></span></template><template x-if="!rate">недоступен</template></span></div>
                    <div class="mt-1 flex justify-between"><span>Комиссия обмена</span><span class="text-slate-700" x-text="fee + '%'"></span></div>
                </div>

                <x-button type="submit" icon="coins" class="w-full rounded-full">Обменять</x-button>
            </form>
        </div>

        {{-- Live rates --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-950">Курси (USD)</h2>
            <p class="mb-3 text-xs text-slate-400">Источник: Binance · Bybit. Обновление каждую минуту.</p>
            <div class="space-y-1.5">
                @foreach($cur as $c)
                <div class="flex items-center justify-between rounded-xl px-3 py-2 hover:bg-slate-50">
                    <span class="flex items-center gap-2 text-sm font-medium text-slate-800">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-[9px] font-bold text-slate-600">{{ substr($c['code'],0,1) }}</span>
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
