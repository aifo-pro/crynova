@extends('layouts.app')
@section('title', __('public.home.title'))
@section('meta_description', __('public.home.subtitle'))

@section('content')
<section class="relative mx-auto max-w-6xl overflow-hidden rounded-b-[2rem] bg-[#fbfbfc] px-4 pb-24 pt-10 sm:px-8">
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-36 hero-grid-floor opacity-80"></div>
    <div class="pointer-events-none absolute left-24 top-20 hidden h-14 w-14 rotate-[-18deg] text-lg sm:grid coin-orbit animate-float">ETH</div>
    <div class="pointer-events-none absolute right-24 top-24 hidden h-12 w-12 rotate-[16deg] text-sm sm:grid coin-orbit animate-float" style="animation-delay: .8s">USDT</div>
    <div class="pointer-events-none absolute left-36 bottom-28 hidden h-18 w-18 rotate-[12deg] text-lg lg:grid coin-orbit animate-float" style="animation-delay: 1.2s">BTC</div>
    <div class="pointer-events-none absolute right-32 bottom-24 hidden h-16 w-16 rotate-[-10deg] text-lg lg:grid coin-orbit animate-float" style="animation-delay: .4s">LTC</div>

    <div class="relative mx-auto max-w-4xl text-center">
        <h1 class="mx-auto max-w-4xl text-5xl font-black leading-[1.05] tracking-[-0.04em] text-[#343434] sm:text-6xl lg:text-7xl">
            {{ __('public.home.hero') }}
        </h1>
        <p class="mx-auto mt-7 max-w-2xl text-xl leading-9 text-slate-700">
            {{ __('public.home.subtitle') }}
        </p>
        <div class="mt-10 flex flex-col justify-center gap-4 sm:flex-row">
            @auth
                <x-button href="{{ route('account.dashboard') }}" class="min-w-64 rounded-full py-4 text-base">{{ __('public.home.dashboard') }}</x-button>
            @else
                <x-button href="{{ route('register') }}" class="min-w-64 rounded-full py-4 text-base">{{ __('public.home.connect') }}</x-button>
            @endauth
            <x-button href="{{ route('developers') }}" variant="secondary" class="min-w-64 rounded-full border-blue-600 py-4 text-base text-blue-600">{{ __('public.home.learn_more') }}</x-button>
        </div>
    </div>

    <div class="relative mx-auto mt-10 h-72 max-w-5xl">
        <div class="absolute left-6 top-4 hidden w-56 rotate-[-4deg] rounded-2xl bg-white p-5 shadow-xl shadow-slate-200 md:block">
            <p class="text-sm text-slate-500">{{ __('public.home.paid_invoices') }}</p>
            <p class="mt-1 text-3xl font-bold text-blue-600">1 238</p>
            <p class="mt-2 text-xs text-emerald-600">+5.04% {{ __('public.home.today') }}</p>
        </div>
        <div class="absolute right-6 top-10 hidden w-56 rotate-[-6deg] rounded-2xl bg-white p-5 shadow-xl shadow-slate-200 md:block">
            <p class="text-sm text-slate-500">{{ __('public.home.total_sales') }}</p>
            <p class="mt-1 text-3xl font-bold text-blue-600">$54 367</p>
            <p class="mt-2 text-xs text-emerald-600">+10.33% {{ __('public.home.today') }}</p>
        </div>
        <div class="absolute inset-x-0 bottom-3 mx-auto max-w-xl rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-2xl shadow-slate-200">
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach([['BTC', '0.42', __('public.home.paid')], ['USDT', '12,440', __('public.home.settled')], ['ETH', '9.28', __('public.home.pending')]] as [$coin, $amount, $status])
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 text-left">
                        <p class="text-xs font-bold text-blue-600">{{ $coin }}</p>
                        <p class="mt-2 text-xl font-bold text-slate-950">{{ $amount }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $status }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-4 py-28 sm:px-0">
    <div class="grid items-center gap-12 lg:grid-cols-[0.85fr_1.15fr]">
        <div>
            <p class="text-lg font-bold text-slate-950">{{ __('public.home.why') }}</p>
            <h2 class="mt-2 text-4xl font-black tracking-[-0.03em] text-blue-600">Crynova</h2>
            <p class="mt-5 text-lg leading-8 text-slate-600">{{ __('public.home.why_text') }}</p>
            <div class="mt-8 grid gap-4">
                @foreach([
                    [__('public.home.feature_fast'), __('public.home.feature_fast_text')],
                    [__('public.home.feature_clarity'), __('public.home.feature_clarity_text')],
                    [__('public.home.feature_control'), __('public.home.feature_control_text')],
                ] as [$title, $text])
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="font-bold text-slate-950">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="relative">
            <img src="{{ asset('assets/crynova/payment-flow.png') }}" alt="Merchant to Crynova crypto payment flow" class="rounded-[2rem] shadow-2xl shadow-slate-200">
        </div>
    </div>
</section>

<section class="bg-[#f7f8fb] py-24">
    <div class="mx-auto max-w-6xl px-4 sm:px-0">
        <div class="text-center">
            <h2 class="text-4xl font-black tracking-[-0.03em] text-slate-950">{{ __('public.home.rails') }}</h2>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-600">{{ __('public.home.rails_text') }}</p>
        </div>
        <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['BTC', 'Bitcoin', 'Bitcoin network', 'BTC'],
                ['ETH', 'Ethereum', 'ERC-20', 'ETH'],
                ['USDT', 'Tether USD', 'ERC-20', 'USDT_ERC20'],
                ['USDT', 'Tether USD', 'TRC-20', 'USDT_TRC20'],
                ['USDT', 'Tether USD', 'BEP-20', 'USDT_BEP20'],
                ['TRX', 'Tron', 'TRC-20', 'TRX'],
                ['LTC', 'Litecoin', 'Litecoin network', 'LTC'],
                ['DOGE', 'Dogecoin', 'Dogecoin network', 'DOGE'],
            ] as [$code, $name, $network, $iconCode])
                <div class="group rounded-3xl border border-slate-200 bg-white p-6 text-left shadow-sm transition hover:-translate-y-1 hover:border-blue-200 hover:shadow-xl hover:shadow-slate-200">
                    <div class="flex items-center gap-4">
                        <div class="grid h-14 w-14 place-items-center rounded-2xl border border-slate-100 bg-slate-50 shadow-sm">
                            <x-coin-icon :code="$iconCode" class="h-9 w-9" />
                        </div>
                        <div>
                            <p class="text-lg font-black text-slate-950">{{ $code }}</p>
                            <p class="text-sm font-semibold text-slate-500">{{ $name }}</p>
                        </div>
                    </div>
                    <div class="mt-5 inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-600">
                        {{ $network }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="border-y border-slate-100 bg-[#f8fafc] py-24">
    <div class="mx-auto max-w-6xl px-4 sm:px-0">
        <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr] lg:items-end">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-600">Payment platform</p>
                <h2 class="mt-3 max-w-xl text-4xl font-black tracking-[-0.03em] text-slate-950">
                    {{ __('public.home.all_for') }}
                </h2>
            </div>
            <p class="max-w-2xl text-lg leading-8 text-slate-600 lg:ml-auto">
                {{ __('public.home.all_for_text') }}
            </p>
        </div>

        <div class="mt-12 grid gap-6 lg:grid-cols-3">
            @foreach([
                [__('public.home.hosted_checkout'), __('public.home.hosted_checkout_text'), 'qr', 'Checkout', '01', 'invoice', 'INV-2049', 'Оплачено', 'bg-emerald-50 text-emerald-700', ['QR-код оплати', 'Таймер рахунку', 'Live-статус платежу']],
                [__('public.home.developer_api'), __('public.home.developer_api_text'), 'database', 'API', '02', 'endpoint', '/v1/invoices', 'Webhook OK', 'bg-blue-50 text-blue-700', ['Invoice API', 'Signed webhooks', 'Payment events']],
                [__('public.home.merchant_cabinet'), __('public.home.merchant_cabinet_text'), 'layout', 'Dashboard', '03', 'amount', '$12,450.75', '+8.7%', 'bg-cyan-50 text-cyan-700', ['KPI та графіки', 'Баланси по валютах', 'Withdrawals control']],
            ] as [$title, $text, $icon, $label, $number, $previewType, $metric, $status, $statusClass, $items])
                <div class="group relative overflow-hidden rounded-[28px] border border-slate-200 bg-white p-8 shadow-[0_18px_45px_rgba(15,23,42,0.06)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_24px_60px_rgba(37,99,235,0.12)]">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-600 via-cyan-400 to-emerald-400 opacity-80"></div>
                    <div class="pointer-events-none absolute -right-12 -top-12 h-36 w-36 rounded-full bg-blue-50 transition duration-300 group-hover:scale-110 group-hover:bg-cyan-50"></div>

                    <div class="relative flex items-center justify-between gap-4">
                        <div class="grid h-12 w-12 place-items-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-100 transition group-hover:scale-105">
                            <x-icon name="{{ $icon }}" class="h-5 w-5" />
                        </div>
                        <div class="text-right">
                            <span class="block text-xs font-black uppercase tracking-[0.18em] text-slate-400">{{ $label }}</span>
                            <span class="mt-1 block text-2xl font-black tracking-[-0.04em] text-slate-200">{{ $number }}</span>
                        </div>
                    </div>

                    <div class="relative">
                        <h3 class="mt-7 text-xl font-black tracking-[-0.01em] text-slate-950">{{ $title }}</h3>
                        <p class="mt-4 min-h-24 text-base leading-7 text-slate-600">{{ $text }}</p>
                    </div>

                    <div class="relative mt-7 overflow-hidden rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-200 to-transparent"></div>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400">{{ __('public.home.active_status') }}</p>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-black leading-none {{ $statusClass }}">{{ $status }}</span>
                        </div>
                        <div class="mt-4 rounded-xl border border-white bg-white px-4 py-3 shadow-sm">
                            @if($previewType === 'endpoint')
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="shrink-0 rounded-lg bg-slate-950 px-2 py-1 text-[11px] font-black text-white">POST</span>
                                    <span class="min-w-0 truncate font-mono text-sm font-bold text-slate-950">{{ $metric }}</span>
                                </div>
                            @elseif($previewType === 'amount')
                                <div class="flex items-end justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-bold text-slate-400">{{ __('public.home.volume') }}</p>
                                        <p class="mt-1 text-xl font-black tracking-[-0.03em] text-slate-950">{{ $metric }}</p>
                                    </div>
                                    <div class="flex h-8 items-end gap-1">
                                        <span class="h-4 w-2 rounded-full bg-blue-200"></span>
                                        <span class="h-7 w-2 rounded-full bg-blue-500"></span>
                                        <span class="h-5 w-2 rounded-full bg-cyan-300"></span>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-bold text-slate-400">{{ __('public.home.invoice') }}</p>
                                        <p class="mt-1 text-lg font-black tracking-[-0.02em] text-slate-950">{{ $metric }}</p>
                                    </div>
                                    <span class="grid h-9 w-9 place-items-center rounded-full bg-emerald-50 text-emerald-600">
                                        <x-icon name="check" class="h-4 w-4" />
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="relative mt-6 space-y-3 border-t border-slate-100 pt-6">
                        @foreach($items as $item)
                            <div class="flex items-center gap-3 text-sm font-semibold text-slate-700">
                                <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-600">
                                    <x-icon name="check" class="h-3.5 w-3.5" />
                                </span>
                                <span>{{ $item }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@endsection
