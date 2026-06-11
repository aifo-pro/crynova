<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('checkout.select.title') }} - Crynova</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $priceAmount = rtrim(rtrim((string) $invoice->price_amount, '0'), '.') ?: '0';
    $merchantInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($invoice->merchant->name ?: 'C', 0, 1));
@endphp
<body class="min-h-screen bg-[#f7f9fc] text-slate-950 antialiased">
<main class="mx-auto flex min-h-screen max-w-2xl flex-col justify-center px-4 py-8 sm:px-6">
    <header class="mb-5 flex items-center gap-3 rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-lg shadow-slate-200/60">
        <x-logo variant="mark" class="h-10 w-10 rounded-2xl shadow-md shadow-blue-600/20" />
        <div>
            <p class="text-base font-black tracking-tight text-slate-950">Crynova Checkout</p>
            <p class="text-xs font-semibold text-slate-500">{{ __('checkout.secure') }}</p>
        </div>
    </header>

    <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl shadow-slate-200/70">
        <div class="border-b border-slate-100 bg-gradient-to-br from-blue-50 via-white to-cyan-50 px-6 py-7 text-center">
            <div class="flex items-center justify-center gap-2">
                <span class="grid h-9 w-9 place-items-center rounded-full bg-blue-600 text-sm font-black text-white">{{ $merchantInitial }}</span>
                <span class="font-bold text-slate-900">{{ $invoice->merchant->name }}</span>
            </div>
            <p class="mt-4 text-sm font-semibold uppercase tracking-wide text-slate-400">{{ __('checkout.select.amount_due') }}</p>
            <p class="mt-1 text-4xl font-black text-slate-950">{{ $priceAmount }} <span class="text-blue-600">{{ $invoice->price_currency }}</span></p>
            @if($invoice->order_id)<p class="mt-1 text-xs text-slate-400">{{ __('checkout.invoice') }} #{{ $invoice->order_id }}</p>@endif
        </div>

        <div class="p-5 sm:p-6">
            <p class="mb-3 text-sm font-bold text-slate-700">{{ __('checkout.select.choose') }}</p>

            @if(empty($options))
                <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-400">{{ __('checkout.select.unavailable') }}</div>
            @else
                <div class="space-y-2.5">
                    @foreach($options as $opt)
                        <form method="POST" action="{{ route('checkout.select-currency', $invoice->uuid) }}">
                            @csrf
                            <input type="hidden" name="currency" value="{{ $opt['code'] }}">
                            <button type="submit" class="flex w-full items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-lg">
                                <x-coin-icon :code="$opt['code']" class="h-9 w-9 shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-slate-950">{{ $opt['code'] }}</p>
                                    <p class="text-xs text-slate-400">{{ $opt['name'] }} · {{ strtoupper($opt['network']) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-mono text-sm font-black text-slate-900">≈ {{ $opt['amount'] }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $opt['code'] }}</p>
                                </div>
                                <x-icon name="arrow-right" class="h-4 w-4 shrink-0 text-slate-300" />
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif

            <p class="mt-4 flex items-start gap-2 text-xs leading-5 text-slate-400">
                <x-icon name="alert-triangle" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                {{ __('checkout.select.note') }}
            </p>
        </div>
    </div>

    <p class="mt-5 text-center text-xs text-slate-400">{{ __('checkout.final.brand_subtitle') }}</p>
</main>
</body>
</html>
