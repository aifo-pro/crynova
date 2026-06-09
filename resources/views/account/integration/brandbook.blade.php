@extends('layouts.app')
@section('title', 'Брендбук')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-950">Брендбук</h1>
            <p class="mt-1 text-slate-500">Логотипи, кольори та фірмовий стиль платіжної сторінки.</p>
        </div>
        @if($merchant)@include('account.integration._picker')@endif
    </div>

    @if(! $merchant)
        @include('account.integration._empty')
    @else
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Logo --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-950">Логотип проекта</h2>
            <div class="flex items-center gap-4">
                <div class="flex h-20 w-20 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50">
                    @if($merchant->logo_path)
                        <img src="{{ asset('storage/'.$merchant->logo_path) }}" alt="logo" class="max-h-16 max-w-16">
                    @else
                        <x-icon name="wallet" class="h-8 w-8 text-slate-300" />
                    @endif
                </div>
                <div>
                    <p class="text-sm text-slate-500">{{ $merchant->logo_path ? 'Логотип загружен.' : 'Логотип не загружен.' }}</p>
                    <a href="{{ route('merchant.settings.project', $merchant) }}" class="mt-1 inline-block text-sm font-semibold text-blue-600 hover:underline">Загрузить / изменить →</a>
                </div>
            </div>
        </div>

        {{-- Brand colors --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-950">Фірмові кольори Crynova</h2>
            <div class="grid grid-cols-4 gap-3">
                @foreach(['#2563eb'=>'Blue 600','#1e40af'=>'Blue 800','#0f172a'=>'Slate 900','#10b981'=>'Emerald'] as $hex=>$label)
                <div>
                    <div class="h-14 w-full rounded-xl border border-slate-200" style="background: {{ $hex }}"></div>
                    <p class="mt-1 text-[11px] font-medium text-slate-600">{{ $label }}</p>
                    <button type="button" class="font-mono text-[10px] text-slate-400 hover:text-blue-600" data-copy-text="{{ $hex }}">{{ $hex }}</button>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Downloadable assets --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-950">Ресурси</h2>
            <div class="space-y-2">
                @foreach(['Логотип Crynova (SVG)','Логотип Crynova (PNG)','Иконки криптовалют','Бейдж «Powered by Crynova»'] as $asset)
                <a href="#" onclick="alert('Загрузка: {{ $asset }} (демо)'); return false;" class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-2.5 text-sm hover:bg-slate-50">
                    <span class="text-slate-700">{{ $asset }}</span>
                    <x-icon name="arrow-right" class="h-4 w-4 text-slate-300" />
                </a>
                @endforeach
            </div>
        </div>

        {{-- Payment page badge --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-950">Бейдж на сайт</h2>
            <p class="mb-3 text-sm text-slate-500">Покажите клиентам, что принимаете крипту через Crynova.</p>
            <div class="mb-3 flex items-center justify-center rounded-2xl bg-slate-50 p-6">
                <span class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
                    <span class="flex h-5 w-5 items-center justify-center rounded bg-blue-600 text-[10px] font-black">C</span> Powered by Crynova
                </span>
            </div>
            <div class="relative rounded-2xl bg-slate-950 p-4">
                <button type="button" class="absolute right-3 top-3 rounded-lg bg-white/10 px-2 py-1 text-xs font-semibold text-white" data-copy-target="badge-code"><span class="text-xs">Copy</span></button>
                <pre id="badge-code" class="overflow-x-auto text-xs text-slate-200"><code>&lt;a href="{{ url('/') }}"&gt;Powered by Crynova&lt;/a&gt;</code></pre>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
