@extends('layouts.app')
@section('title', __('merchant_settings.integration.title', ['name' => $merchant->name]))

@section('content')
<div class="space-y-6">
    @include('merchant.settings._tabs')
    @if(session('new_secret'))
        <x-alert variant="warning" title="{{ __('merchant_settings.integration.secret_created') }}">
            <code id="new-secret" class="mt-2 block break-all rounded-lg bg-black/5 p-2 font-mono text-sm">{{ session('new_secret') }}</code>
            <x-button type="button" variant="secondary" data-copy-target="new-secret" class="mt-2" icon="copy">{{ __('merchant_settings.common.copy') }}</x-button>
        </x-alert>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="grid gap-4 sm:grid-cols-[1fr_1.4fr] sm:items-start">
            <div>
                <h2 class="text-base font-semibold text-slate-950">API keys</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('merchant_settings.integration.keys_text') }}</p>
            </div>
            <div class="space-y-3">
                <div class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5">
                    <span class="w-16 text-xs text-slate-400">API key</span>
                    <span class="flex-1 truncate font-mono text-sm text-blue-600">{{ $merchant->maskedApiKey() ?? '—' }}</span>
                    @if($merchant->api_key)<button type="button" class="text-slate-300 hover:text-blue-600" data-copy-text="{{ $merchant->api_key }}"><x-icon name="copy" class="h-4 w-4" /></button>@endif
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5">
                    <span class="w-16 text-xs text-slate-400">Shop ID</span>
                    <span class="flex-1 truncate font-mono text-sm text-blue-600">{{ $merchant->shop_id }}</span>
                    <button type="button" class="text-slate-300 hover:text-blue-600" data-copy-text="{{ $merchant->shop_id }}"><x-icon name="copy" class="h-4 w-4" /></button>
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5">
                    <span class="w-16 text-xs text-slate-400">Secret</span>
                    @if($secret)
                        <span class="flex-1 truncate font-mono text-sm text-blue-600">••••••••{{ substr($secret, -4) }}</span>
                        <button type="button" class="text-slate-300 hover:text-blue-600" data-copy-text="{{ $secret }}"><x-icon name="copy" class="h-4 w-4" /></button>
                    @else
                        <form method="POST" action="{{ route('merchant.settings.integration.secret', $merchant) }}" class="flex-1">
                            @csrf
                            <button type="submit" class="text-sm font-semibold text-blue-600 hover:underline">{{ __('merchant_settings.common.create_new') }}</button>
                        </form>
                    @endif
                </div>
                @if($secret)
                    <form method="POST" action="{{ route('merchant.settings.integration.secret', $merchant) }}">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-blue-600 hover:underline">{{ __('merchant_settings.common.create_new') }} secret</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('merchant.settings.integration.update', $merchant) }}" class="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @csrf

        <div class="grid gap-4 sm:grid-cols-[1fr_1.4fr] sm:items-start">
            <div><h2 class="text-base font-semibold text-slate-950">{{ __('merchant_settings.integration.site') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('merchant_settings.integration.site_text') }}</p></div>
            <input name="domain" type="text" value="{{ old('domain', $merchant->website ?: ($merchant->domain ? 'https://'.$merchant->domain : '')) }}" class="fin-input" placeholder="https://domain.com/">
        </div>

        <hr class="border-slate-100">

        <div class="grid gap-4 sm:grid-cols-[1fr_1.4fr] sm:items-start">
            <div><h2 class="text-base font-semibold text-slate-950">{{ __('merchant_settings.integration.cms') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('merchant_settings.integration.cms_text') }}</p></div>
            <x-cms-select name="cms" :options="$cmsList" :selected="$merchant->cms" :placeholder="__('merchant_settings.integration.choose_cms')" />
        </div>

        <hr class="border-slate-100">

        @foreach([
            ['success_url', __('merchant_settings.integration.success_url'), __('merchant_settings.integration.success_url_text'), 'https://domain.com/successful-payment'],
            ['fail_url', __('merchant_settings.integration.fail_url'), __('merchant_settings.integration.fail_url_text'), 'https://domain.com/failed-payment'],
            ['callback_url', __('merchant_settings.integration.callback_url'), __('merchant_settings.integration.callback_url_text'), 'https://domain.com/callback'],
        ] as [$name, $title, $text, $placeholder])
            <div class="grid gap-4 sm:grid-cols-[1fr_1.4fr] sm:items-start">
                <div><h2 class="text-base font-semibold text-slate-950">{{ $title }}</h2><p class="mt-1 text-sm text-slate-500">{{ $text }}</p></div>
                <input name="{{ $name }}" type="url" value="{{ old($name, $merchant->{$name}) }}" class="fin-input" placeholder="{{ $placeholder }}">
            </div>
            <hr class="border-slate-100">
        @endforeach

        <div class="grid gap-4 sm:grid-cols-[1fr_1.4fr] sm:items-start" x-data="{ fmt: '{{ old('postback_format', $merchant->postback_format) }}' }">
            <div><h2 class="text-base font-semibold text-slate-950">{{ __('merchant_settings.integration.postback') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('merchant_settings.integration.postback_text') }}</p></div>
            <div class="grid grid-cols-2 gap-3">
                <input type="hidden" name="postback_format" :value="fmt">
                <button type="button" @click="fmt='form-data'" :class="fmt === 'form-data' ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200'" class="rounded-xl border px-4 py-2.5 text-sm font-semibold text-slate-700">form-data</button>
                <button type="button" @click="fmt='json'" :class="fmt === 'json' ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200'" class="rounded-xl border px-4 py-2.5 text-sm font-semibold text-slate-700">json</button>
            </div>
        </div>

        <hr class="border-slate-100">
        <div class="flex justify-end"><x-button type="submit" class="rounded-full px-8">{{ __('merchant_settings.common.save') }}</x-button></div>
    </form>
</div>
@endsection
