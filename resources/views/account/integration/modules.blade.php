@extends('layouts.app')
@section('title', __('account.integration.modules_title'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-950">{{ __('account.integration.modules_title') }}</h1>
            <p class="mt-1 text-slate-500">{{ __('account.integration.modules_text') }}</p>
        </div>
        @if($merchant)@include('account.integration._picker')@endif
    </div>

    @if(! $merchant)
        @include('account.integration._empty')
    @else
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-950">{{ __('account.integration.credentials', ['name' => $merchant->name]) }}</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5">
                    <span class="w-16 text-xs text-slate-400">Shop ID</span>
                    <span class="flex-1 truncate font-mono text-sm text-blue-600">{{ $merchant->shop_id }}</span>
                    <button type="button" class="text-slate-300 hover:text-blue-600" data-copy-text="{{ $merchant->shop_id }}"><x-icon name="copy" class="h-4 w-4" /></button>
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5">
                    <span class="w-16 text-xs text-slate-400">API key</span>
                    <span class="flex-1 truncate font-mono text-sm text-blue-600">{{ $merchant->maskedApiKey() ?? '—' }}</span>
                    @if($merchant->api_key)<button type="button" class="text-slate-300 hover:text-blue-600" data-copy-text="{{ $merchant->api_key }}"><x-icon name="copy" class="h-4 w-4" /></button>@endif
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($modules as $mod)
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600"><x-icon :name="$mod['icon']" class="h-5 w-5" /></span>
                        <div>
                            <p class="font-semibold text-slate-950">{{ $mod['name'] }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $mod['desc'] }}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="#" onclick="alert('{{ __('account.integration.download_demo', ['name' => $mod['name']]) }}'); return false;" class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                            <x-icon name="arrow-right" class="h-3.5 w-3.5" /> {{ __('account.integration.download') }}
                        </a>
                        <a href="{{ route('developers') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            <x-icon name="book" class="h-3.5 w-3.5" /> {{ __('account.integration.instructions') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
