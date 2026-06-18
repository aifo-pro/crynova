@extends('layouts.app')
@section('title', __('public.pricing.title'))
@section('meta_description', __('public.pricing.meta'))

@php
    $plans = [
        ['key'=>'starter', 'name'=>'Starter', 'badge'=>__('public.pricing.badge'), 'highlight'=>true,
         'price'=>'0.8%', 'cta'=>'start'],
        ['key'=>'growth', 'name'=>'Growth', 'badge'=>null, 'highlight'=>false,
         'price'=>__('public.pricing.price_custom'), 'cta'=>'contact'],
        ['key'=>'enterprise', 'name'=>'Enterprise', 'badge'=>null, 'highlight'=>false,
         'price'=>__('public.pricing.price_custom'), 'cta'=>'contact'],
    ];
    $icons = ['qr','key','link','shield','wallet','banknote','coins','gauge'];
    $included = (array) __('public.pricing.included');
    $faq = (array) __('public.pricing.faq');
@endphp

@push('jsonld')
@php
    // FAQPage — mirrors the FAQ accordion visibly rendered below.
    $faqLd = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => array_values(array_map(fn ($item) => [
            '@type'          => 'Question',
            'name'           => $item[0] ?? '',
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item[1] ?? ''],
        ], array_filter($faq, fn ($i) => is_array($i) && isset($i[0], $i[1])))),
    ];
@endphp
@if(!empty($faqLd['mainEntity']))
<script type="application/ld+json">{!! json_encode($faqLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@endpush

@push('jsonld')
<script type="application/ld+json">{!! json_encode([
    '@context'=>'https://schema.org','@type'=>'Product','name'=>'Crynova crypto payment gateway',
    'description'=>__('public.pricing.meta'),
    'offers'=>['@type'=>'Offer','price'=>'0.8','priceCurrency'=>'PCT','description'=>'0.8% per paid invoice'],
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
<section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
    {{-- Hero --}}
    <div class="mx-auto max-w-3xl text-center">
        <span class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-blue-700">{{ __('public.pricing.badge') }}</span>
        <h1 class="mt-5 text-4xl font-black tracking-[-0.02em] text-slate-950 sm:text-5xl">{{ __('public.pricing.h1') }}</h1>
        <p class="mt-5 text-lg leading-8 text-slate-600">{{ __('public.pricing.sub') }}</p>
        <div class="mt-7 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm font-medium text-slate-500">
            @foreach(['perk1','perk2','perk3'] as $perk)
                <span class="inline-flex items-center gap-1.5"><x-icon name="check" class="h-4 w-4 text-emerald-500" /> {{ __('public.pricing.'.$perk) }}</span>
            @endforeach
        </div>
    </div>

    {{-- Plans --}}
    <div class="mt-12 grid items-stretch gap-6 lg:grid-cols-3">
        @foreach($plans as $plan)
            @php $features = (array) __('public.pricing.'.$plan['key'].'_features'); @endphp
            <div class="relative flex flex-col rounded-3xl border bg-white p-7 shadow-sm transition hover:-translate-y-0.5 hover:shadow-xl
                        {{ $plan['highlight'] ? 'border-blue-300 ring-1 ring-blue-200' : 'border-slate-200' }}">
                @if($plan['badge'])
                    <span class="absolute -top-3 right-6 rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 px-3 py-1 text-xs font-bold text-white shadow-md">{{ $plan['badge'] }}</span>
                @endif
                <h2 class="text-xl font-black text-slate-950">{{ $plan['name'] }}</h2>
                <p class="mt-2 min-h-10 text-sm leading-6 text-slate-500">{{ __('public.pricing.'.$plan['key'].'_desc') }}</p>

                <div class="mt-5 flex items-baseline gap-1">
                    <span class="text-5xl font-black tracking-tight text-slate-950">{{ $plan['price'] }}</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ __('public.pricing.'.$plan['key'].'_unit') }}</p>

                <div class="my-6 h-px bg-slate-100"></div>

                <ul class="space-y-3">
                    @foreach($features as $feature)
                        <li class="flex items-start gap-3 text-sm text-slate-700">
                            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-600"><x-icon name="check" class="h-3.5 w-3.5" /></span>
                            {{ $feature }}
                        </li>
                    @endforeach
                </ul>

                <a href="{{ $plan['cta'] === 'contact' ? lroute('contact') : route('register') }}"
                   class="mt-8 inline-flex w-full items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-bold transition
                          {{ $plan['highlight'] ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white hover:opacity-90' : 'border border-slate-200 text-slate-900 hover:bg-slate-50' }}">
                    {{ $plan['cta'] === 'contact' ? __('public.pricing.cta_contact') : __('public.pricing.cta_start') }} <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>
        @endforeach
    </div>

    {{-- What's included --}}
    <div class="mt-16">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-black text-slate-950">{{ __('public.pricing.included_title') }}</h2>
            <p class="mt-3 text-slate-600">{{ __('public.pricing.included_sub') }}</p>
        </div>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($included as $i => $item)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-200 hover:shadow-md">
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-blue-50 text-blue-600"><x-icon :name="$icons[$i] ?? 'check'" class="h-5 w-5" /></span>
                    <p class="mt-4 font-bold text-slate-950">{{ $item[0] }}</p>
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ $item[1] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Fee example --}}
    <div class="mt-16 grid gap-6 rounded-3xl border border-blue-100 bg-gradient-to-br from-blue-50 via-white to-cyan-50 p-8 sm:grid-cols-3 sm:p-10">
        <div class="sm:col-span-1">
            <h2 class="text-2xl font-black text-slate-950">{{ __('public.pricing.fee_title') }}</h2>
            <p class="mt-3 text-sm leading-7 text-slate-600">{{ __('public.pricing.fee_text_pre') }} <span class="font-bold text-blue-700">0.8%</span> {{ __('public.pricing.fee_text_post') }}</p>
        </div>
        <div class="grid grid-cols-3 gap-4 sm:col-span-2">
            @foreach([['$100','$0.80','$99.20'],['$1,000','$8.00','$992'],['$10,000','$80.00','$9,920']] as [$amt,$fee,$keep])
                <div class="rounded-2xl border border-white bg-white/70 p-4 text-center shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('public.pricing.fee_invoice') }}</p>
                    <p class="mt-1 text-xl font-black text-slate-950">{{ $amt }}</p>
                    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('public.pricing.fee_fee') }}</p>
                    <p class="mt-1 text-lg font-bold text-blue-600">{{ $fee }}</p>
                    <p class="mt-2 text-xs text-slate-500">{{ __('public.pricing.fee_keep', ['v' => $keep]) }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- FAQ --}}
    <div class="mt-16">
        <h2 class="text-center text-3xl font-black text-slate-950">{{ __('public.pricing.faq_title') }}</h2>
        <div class="mx-auto mt-8 max-w-3xl space-y-3" x-data="{ open: 0 }">
            @foreach($faq as $i => $item)
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <button type="button" @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                            class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left">
                        <span class="font-semibold text-slate-950">{{ $item[0] }}</span>
                        <x-icon name="chevron-down" class="h-5 w-5 shrink-0 text-slate-400 transition" ::class="open === {{ $i }} ? 'rotate-180' : ''" />
                    </button>
                    <div x-show="open === {{ $i }}" x-cloak x-transition.opacity>
                        <p class="px-5 pb-5 text-sm leading-7 text-slate-600">{{ $item[1] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- CTA --}}
    <div class="mt-16 flex flex-col items-center gap-4 rounded-3xl bg-gradient-to-br from-blue-600 to-indigo-600 p-10 text-center text-white sm:flex-row sm:justify-between sm:text-left">
        <div>
            <h2 class="text-2xl font-black sm:text-3xl">{{ __('public.pricing.cta_title') }}</h2>
            <p class="mt-2 text-blue-50">{{ __('public.pricing.cta_text') }}</p>
        </div>
        <a href="{{ route('register') }}" class="inline-flex shrink-0 items-center gap-2 rounded-full bg-white px-7 py-3.5 text-sm font-bold text-blue-600 hover:bg-blue-50">{{ __('public.pricing.cta_btn') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
    </div>
</section>
@endsection
