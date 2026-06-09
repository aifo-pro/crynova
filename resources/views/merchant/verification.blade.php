@extends('layouts.app')
@section('title', __('merchant.verification.title_with_name', ['name' => $merchant->name]))

@section('content')
@php $isTelegram = $merchant->merchant_type === 'telegram'; @endphp
<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('merchant.control', $merchant) }}" class="text-slate-400 hover:text-blue-600"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <div>
            <h1 class="text-3xl font-semibold text-slate-950">{{ __('merchant.verification.title') }}</h1>
            <p class="mt-1 text-slate-500">{{ $isTelegram ? __('merchant.verification.telegram_intro') : __('merchant.verification.domain_intro') }}</p>
        </div>
    </div>
    <div class="rounded-3xl border border-blue-200 bg-blue-50 p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ __('merchant.verification.code') }}</p>
        <div class="mt-2 flex items-center gap-2">
            <code id="verify-code" class="flex-1 break-all rounded-xl bg-white px-3 py-2 font-mono text-sm text-blue-900">{{ $code }}</code>
            <button type="button" data-copy-target="verify-code" class="rounded-xl border border-blue-200 bg-white px-3 py-2 text-xs font-semibold text-blue-600 hover:bg-blue-100">{{ __('merchant.verification.copy') }}</button>
        </div>
    </div>

    @if($isTelegram)
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="font-semibold text-slate-950">{{ __('merchant.verification.telegram') }}</p>
            <ol class="mt-4 space-y-3 text-sm text-slate-600">
                <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-semibold text-blue-600">1</span> {{ __('merchant.verification.telegram_step_1') }}</li>
                <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-semibold text-blue-600">2</span> {{ __('merchant.verification.telegram_step_2') }}</li>
                <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-semibold text-blue-600">3</span> {!! __('merchant.verification.telegram_step_3', ['url' => '<code class="text-blue-600">t.me/'.$merchant->telegram_channel.'</code>']) !!}</li>
            </ol>
            <form method="POST" action="{{ route('merchant.verification.verify', $merchant) }}" class="mt-5">
                @csrf
                <input type="hidden" name="method" value="telegram">
                <x-button type="submit" icon="shield-check">{{ __('merchant.verification.verify_telegram') }}</x-button>
            </form>
        </div>
    @else
        <div class="space-y-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="font-semibold text-slate-950">{{ __('merchant.verification.method_file') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('merchant.verification.method_file_text') }}</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="fin-label text-xs">{{ __('merchant.verification.file_name') }}</label>
                        <input readonly class="fin-input font-mono text-xs" value="{{ $verification['file_name'] }}">
                    </div>
                    <div>
                        <label class="fin-label text-xs">{{ __('merchant.verification.file_content') }}</label>
                        <input readonly class="fin-input font-mono text-xs" value="{{ $verification['file_content'] }}">
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-400">{{ __('merchant.verification.expected_url') }}: <code class="text-blue-600">{{ $verification['file_url'] }}</code></p>
                <form method="POST" action="{{ route('merchant.verification.verify', $merchant) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="method" value="file">
                    <x-button type="submit" variant="secondary" icon="shield-check">{{ __('merchant.verification.verify_file') }}</x-button>
                </form>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="font-semibold text-slate-950">{{ __('merchant.verification.method_homepage') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('merchant.verification.method_homepage_text') }}</p>
                <div class="mt-3 flex items-center gap-2">
                    <code id="homepage-code" class="flex-1 break-all rounded-xl bg-slate-50 px-3 py-2 font-mono text-xs text-slate-700">{{ $verification['homepage_code'] }}</code>
                    <button type="button" data-copy-target="homepage-code" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">{{ __('merchant.verification.copy') }}</button>
                </div>
                <form method="POST" action="{{ route('merchant.verification.verify', $merchant) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="method" value="homepage">
                    <x-button type="submit" variant="secondary" icon="shield-check">{{ __('merchant.verification.verify_homepage') }}</x-button>
                </form>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="font-semibold text-slate-950">{{ __('merchant.verification.method_dns') }}</p>
                <p class="mt-1 text-sm text-slate-500">{!! __('merchant.verification.method_dns_text', ['host' => '<code class="text-blue-600">'.$verification['dns_host'].'</code>']) !!}</p>
                <div class="mt-3 flex items-center gap-2">
                    <code id="dns-code" class="flex-1 break-all rounded-xl bg-slate-50 px-3 py-2 font-mono text-xs text-slate-700">{{ $verification['dns_record'] }}</code>
                    <button type="button" data-copy-target="dns-code" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">{{ __('merchant.verification.copy') }}</button>
                </div>
                <p class="mt-2 text-xs text-slate-400">{{ __('merchant.verification.dns_note') }}</p>
                <form method="POST" action="{{ route('merchant.verification.verify', $merchant) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="method" value="dns">
                    <x-button type="submit" variant="secondary" icon="shield-check">{{ __('merchant.verification.verify_dns') }}</x-button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
