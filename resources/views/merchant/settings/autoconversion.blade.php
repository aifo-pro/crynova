@extends('layouts.app')
@section('title', __('merchant_settings.autoconversion.title', ['name' => $merchant->name]))

@section('content')
<div class="space-y-6" x-data="{ enabled: {{ $merchant->autoconvert_enabled ? 'true' : 'false' }} }">
    @include('merchant.settings._tabs')
    <form method="POST" action="{{ route('merchant.settings.autoconversion.update', $merchant) }}"
          class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @csrf
        <input type="hidden" name="autoconvert_enabled" :value="enabled ? 1 : 0">

        <div class="mb-6 flex items-center gap-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><x-icon name="arrow-trend-up" class="h-5 w-5" /></span>
            <h2 class="text-lg font-semibold text-slate-950">{{ __('merchant_settings.autoconversion.heading') }}</h2>
        </div>

        {{-- Enable toggle --}}
        <div class="grid gap-4 sm:grid-cols-[1fr_1.2fr] sm:items-start">
            <div>
                <h3 class="text-base font-semibold text-slate-950">{{ __('merchant_settings.autoconversion.auto') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('merchant_settings.autoconversion.text') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm font-semibold" :class="enabled ? 'text-blue-600' : 'text-slate-400'" x-text="enabled ? '{{ __('merchant_settings.common.enabled') }}' : '{{ __('merchant_settings.common.disabled') }}'"></span>
                <button type="button" @click="enabled=!enabled" role="switch" :class="enabled ? 'bg-blue-600' : 'bg-slate-200'" class="relative inline-flex h-5 w-9 items-center rounded-full transition">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition" :class="enabled ? 'translate-x-4' : 'translate-x-1'"></span>
                </button>
            </div>
        </div>

        {{-- Target currency --}}
        <div x-show="enabled" x-cloak>
            <hr class="my-6 border-slate-100">
            <div class="grid gap-4 sm:grid-cols-[1fr_1.2fr] sm:items-start">
                <div>
                    <h3 class="text-base font-semibold text-slate-950">{{ __('merchant_settings.autoconversion.target') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('merchant_settings.autoconversion.target_text') }}</p>
                </div>
                <select name="autoconvert_target_currency_id" class="fin-input">
                    <option value="">{{ __('merchant_settings.autoconversion.choose') }}</option>
                    @foreach($targets as $t)
                        <option value="{{ $t->id }}" @selected($merchant->autoconvert_target_currency_id == $t->id)>{{ $t->code }} — {{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mt-4 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700">
                <p class="flex items-start gap-2">
                    <x-icon name="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0" />
                    {{ __('merchant_settings.autoconversion.notice') }}
                </p>
            </div>
        </div>

        <hr class="my-6 border-slate-100">
        <div class="flex justify-end"><x-button type="submit" class="rounded-full px-8">{{ __('merchant_settings.common.save') }}</x-button></div>
    </form>
</div>
@endsection
