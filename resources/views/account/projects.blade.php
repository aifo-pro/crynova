@extends('layouts.app')
@section('title', __('account.projects.title'))

@section('content')
<div class="space-y-6" x-data="{ view: localStorage.getItem('projview') || 'list', set(v){ this.view=v; localStorage.setItem('projview', v); } }">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-slate-950">{{ __('account.projects.title') }} <span class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-xs text-slate-400">?</span></h1>
    </div>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1">
            <button type="button" @click="set('list')" :class="view==='list' ? 'bg-blue-50 text-blue-600' : 'text-slate-400'" class="flex h-8 w-8 items-center justify-center rounded-lg transition"><x-icon name="layers" class="h-4 w-4" /></button>
            <button type="button" @click="set('grid')" :class="view==='grid' ? 'bg-blue-50 text-blue-600' : 'text-slate-400'" class="flex h-8 w-8 items-center justify-center rounded-lg transition"><x-icon name="layout" class="h-4 w-4" /></button>
        </div>
        <x-button href="{{ route('account.merchants.create') }}" icon="plus" class="rounded-full">{{ __('account.projects.add') }}</x-button>
    </div>

    @if($merchants->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600"><x-icon name="landmark" class="h-7 w-7" /></span>
            <p class="text-lg font-semibold text-slate-950">{{ __('account.projects.empty_title') }}</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ __('account.projects.empty_text') }}</p>
            <x-button href="{{ route('account.merchants.create') }}" icon="plus" class="mt-5">{{ __('account.projects.create') }}</x-button>
        </div>
    @else
        <div :class="view==='grid' ? 'grid gap-5 lg:grid-cols-2' : 'space-y-5'">
            @foreach($merchants as $merchant)
                @php
                    $statusMeta = $merchant->statusMeta();
                    $payUrl = $merchant->paymentPageUrl();
                    $site = $merchant->website ?: ($merchant->domain ? 'https://'.$merchant->domain : null);
                    $apiFull = $merchant->api_key;
                @endphp
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500"><x-icon name="wallet" class="h-5 w-5" /></span>
                            <p class="text-lg font-semibold text-slate-950">{{ $merchant->name }}</p>
                        </div>
                        <a href="{{ route('merchant.settings.project', $merchant) }}" class="text-sm font-semibold text-blue-600 hover:underline">{{ __('account.projects.settings') }}</a>
                    </div>

                    <hr class="my-4 border-slate-100">

                    <div class="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-slate-400">{{ __('account.projects.site') }}</span>
                            @if($site)<span class="flex min-w-0 items-center gap-1.5"><a href="{{ $site }}" target="_blank" class="truncate text-sm text-blue-600 hover:underline">{{ $site }}</a><button type="button" class="shrink-0 text-slate-300 hover:text-blue-600" data-copy-text="{{ $site }}"><x-icon name="copy" class="h-3.5 w-3.5" /></button></span>@else<span class="text-sm text-slate-400">-</span>@endif
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-slate-400">API key</span>
                            @if($apiFull)<span class="flex min-w-0 items-center gap-1.5"><span class="truncate font-mono text-sm text-blue-600">{{ $merchant->maskedApiKey() }}</span><button type="button" class="shrink-0 text-slate-300 hover:text-blue-600" data-copy-text="{{ $apiFull }}"><x-icon name="copy" class="h-3.5 w-3.5" /></button></span>@else<span class="text-sm text-slate-400">-</span>@endif
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-slate-400">{{ __('account.projects.payment_page') }}</span>
                            <span class="flex min-w-0 items-center gap-1.5"><a href="{{ $payUrl }}" target="_blank" class="truncate text-sm text-blue-600 hover:underline">{{ \Illuminate\Support\Str::limit($payUrl, 28) }}</a><button type="button" class="shrink-0 text-slate-300 hover:text-blue-600" data-copy-text="{{ $payUrl }}"><x-icon name="copy" class="h-3.5 w-3.5" /></button></span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-slate-400">Shop ID</span>
                            <span class="flex min-w-0 items-center gap-1.5"><span class="truncate font-mono text-sm text-blue-600">{{ $merchant->shop_id }}</span><button type="button" class="shrink-0 text-slate-300 hover:text-blue-600" data-copy-text="{{ $merchant->shop_id }}"><x-icon name="copy" class="h-3.5 w-3.5" /></button></span>
                        </div>
                    </div>

                    <hr class="my-4 border-slate-100">

                    <div class="flex items-center justify-between">
                        <form method="POST" action="{{ route('merchant.test-mode', $merchant) }}" class="flex items-center gap-2">
                            @csrf
                            <span class="text-sm text-slate-500">{{ __('account.projects.test_mode') }}:</span>
                            <button type="submit" role="switch" class="relative inline-flex h-5 w-9 items-center rounded-full transition {{ $merchant->test_mode ? 'bg-blue-600' : 'bg-slate-200' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition {{ $merchant->test_mode ? 'translate-x-4' : 'translate-x-1' }}"></span>
                            </button>
                        </form>
                        <span class="text-sm font-semibold text-{{ $statusMeta['color'] }}-600">{{ $statusMeta['label'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
