@extends('layouts.app')
@section('title', __('public.coins_page.title'))
@section('meta_description', __('public.coins_page.meta'))

@php
    $coins = \App\Models\Currency::where('is_active', true)->orderBy('code')->get();
    $fiat  = (array) config('crynova.fiat_currencies', []);
    $networkLabel = fn($code, $network) => match(true) {
        str_contains($code, 'ERC20') => 'ERC-20',
        str_contains($code, 'TRC20') => 'TRC-20',
        str_contains($code, 'BEP20') => 'BEP-20',
        default => strtoupper((string) $network),
    };
@endphp

@push('jsonld')
<script type="application/ld+json">{!! json_encode([
    '@context'=>'https://schema.org','@type'=>'WebPage',
    'name'=>__('public.coins_page.title'),'description'=>__('public.coins_page.meta'),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
<section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
    {{-- Hero --}}
    <div class="max-w-2xl">
        <span class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-blue-700">{{ __('public.coins_page.badge') }}</span>
        <h1 class="mt-5 text-4xl font-black tracking-[-0.02em] text-slate-950 sm:text-5xl">{{ __('public.coins_page.heading') }}</h1>
        <p class="mt-5 text-lg leading-8 text-slate-600">{{ __('public.coins_page.subtitle') }}</p>
    </div>

    {{-- Crypto grid --}}
    @if($coins->isEmpty())
        <div class="mt-12 rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-400">{{ __('public.coins_page.empty') }}</div>
    @else
        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($coins as $coin)
                <div class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-xl">
                    <div class="flex items-center gap-3">
                        <x-coin-icon :code="$coin->code" class="h-12 w-12 shrink-0" />
                        <div class="min-w-0">
                            <p class="truncate text-lg font-black text-slate-950">{{ $coin->code }}</p>
                            <p class="truncate text-sm text-slate-500">{{ $coin->name }}</p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">{{ __('public.coins_page.network') }}</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide text-slate-600">{{ $networkLabel($coin->code, $coin->network) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">{{ __('public.coins_page.confirmations') }}</span>
                            <span class="font-semibold text-slate-700">{{ $coin->confirmations_required }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">{{ __('public.coins_page.min') }}</span>
                            <span class="font-mono text-slate-700">{{ rtrim(rtrim((string) $coin->min_amount, '0'), '.') ?: '—' }} {{ $coin->code }}</span>
                        </div>
                        @if($coin->supports_memo)
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-amber-600"><x-icon name="alert-triangle" class="h-3.5 w-3.5" /> {{ __('public.coins_page.memo') }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Fiat section --}}
    <div class="mt-14 rounded-3xl border border-blue-100 bg-gradient-to-br from-blue-50 via-white to-cyan-50 p-6 sm:p-8">
        <div class="flex items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-2xl bg-blue-600 text-white"><x-icon name="banknote" class="h-6 w-6" /></span>
            <h2 class="text-2xl font-black text-slate-950">{{ __('public.coins_page.fiat_title') }}</h2>
        </div>
        <p class="mt-3 max-w-2xl leading-7 text-slate-600">{{ __('public.coins_page.fiat_text') }}</p>
        <div class="mt-5 flex flex-wrap gap-2">
            @foreach($fiat as $code)
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-bold text-slate-700 shadow-sm">{{ $code }}</span>
            @endforeach
        </div>
    </div>

    {{-- CTA --}}
    <div class="mt-12 flex flex-col items-center gap-4 rounded-3xl bg-gradient-to-br from-blue-600 to-indigo-600 p-8 text-center text-white sm:flex-row sm:justify-between sm:text-left">
        <div>
            <h2 class="text-2xl font-black">{{ __('public.coins_page.cta_title') }}</h2>
            <p class="mt-1 text-blue-50">{{ __('public.coins_page.cta_text') }}</p>
        </div>
        <a href="{{ route('register') }}" class="inline-flex shrink-0 items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-bold text-blue-600 hover:bg-blue-50">{{ __('public.coins_page.cta_btn') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
    </div>
</section>
@endsection
