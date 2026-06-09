@extends('layouts.app')
@section('title', __('merchant_settings.fees.title', ['name' => $merchant->name]))

@section('content')
<div class="space-y-6"
     x-data="{
        transfer: '{{ old('transfer_fee_payer', $merchant->transfer_fee_payer) }}',
        service: '{{ old('service_fee_payer', $merchant->service_fee_payer) }}',
        unit: '{{ old('partial_confirm_unit', $merchant->partial_confirm_unit) }}',
        aml: {{ $merchant->aml_enabled ? 'true' : 'false' }},
     }">
    @include('merchant.settings._tabs')
    <form method="POST" action="{{ route('merchant.settings.fees.update', $merchant) }}"
          class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 space-y-8">
        @csrf
        <input type="hidden" name="transfer_fee_payer" :value="transfer">
        <input type="hidden" name="service_fee_payer" :value="service">
        <input type="hidden" name="partial_confirm_unit" :value="unit">
        <input type="hidden" name="aml_enabled" :value="aml ? 1 : 0">

        {{-- Transfer fee --}}
        <div class="grid gap-4 sm:grid-cols-[1fr_1.2fr] sm:items-start">
            <div>
                <h2 class="text-base font-semibold text-slate-950">{{ __('merchant_settings.fees.transfer_title') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('merchant_settings.fees.transfer_text') }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <button type="button" @click="transfer='client'" :class="transfer==='client' ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200'" class="flex items-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold text-slate-700"><x-icon name="user" class="h-4 w-4" /> {{ __('merchant_settings.fees.client') }}</button>
                <button type="button" @click="transfer='merchant'" :class="transfer==='merchant' ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200'" class="flex items-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold text-slate-700"><x-icon name="wallet" class="h-4 w-4" /> {{ __('merchant_settings.fees.merchant') }}</button>
            </div>
        </div>

        <hr class="border-slate-100">

        {{-- Service fee --}}
        <div class="grid gap-4 sm:grid-cols-[1fr_1.2fr] sm:items-start">
            <div>
                <h2 class="text-base font-semibold text-slate-950">{{ __('merchant_settings.fees.service_title') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('merchant_settings.fees.service_text') }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <button type="button" @click="service='client'" :class="service==='client' ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200'" class="flex items-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold text-slate-700"><x-icon name="user" class="h-4 w-4" /> {{ __('merchant_settings.fees.client') }}</button>
                <button type="button" @click="service='merchant'" :class="service==='merchant' ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200'" class="flex items-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold text-slate-700"><x-icon name="wallet" class="h-4 w-4" /> {{ __('merchant_settings.fees.merchant') }}</button>
            </div>
        </div>

        <hr class="border-slate-100">

        {{-- Partial auto-confirm --}}
        <div class="grid gap-4 sm:grid-cols-[1fr_1.2fr] sm:items-start">
            <div>
                <h2 class="text-base font-semibold text-slate-950">{{ __('merchant_settings.fees.partial_title') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('merchant_settings.fees.partial_text') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <input name="partial_confirm_value" type="number" step="any" min="0" value="{{ old('partial_confirm_value', $merchant->partial_confirm_value) }}" class="fin-input flex-1">
                <div class="flex overflow-hidden rounded-lg border border-slate-200 text-sm font-semibold">
                    <button type="button" @click="unit='fixed'" :class="unit==='fixed' ? 'bg-slate-900 text-white' : 'text-slate-500'" class="px-3 py-2">$</button>
                    <button type="button" @click="unit='percent'" :class="unit==='percent' ? 'bg-slate-900 text-white' : 'text-slate-500'" class="px-3 py-2">%</button>
                </div>
            </div>
        </div>

        <hr class="border-slate-100">

        {{-- AML --}}
        <div class="grid gap-4 sm:grid-cols-[1fr_1.2fr] sm:items-start">
            <div>
                <h2 class="text-base font-semibold text-slate-950">{{ __('merchant_settings.fees.aml_title') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('merchant_settings.fees.aml_text') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm font-semibold" :class="aml ? 'text-blue-600' : 'text-slate-400'" x-text="aml ? '{{ __('merchant_settings.common.enabled') }}' : '{{ __('merchant_settings.common.disabled') }}'"></span>
                <button type="button" @click="aml=!aml" role="switch" :class="aml ? 'bg-blue-600' : 'bg-slate-200'" class="relative inline-flex h-5 w-9 items-center rounded-full transition">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition" :class="aml ? 'translate-x-4' : 'translate-x-1'"></span>
                </button>
            </div>
        </div>

        <hr class="border-slate-100">
        <div class="flex justify-end"><x-button type="submit" class="rounded-full px-8">{{ __('merchant_settings.common.save') }}</x-button></div>
    </form>
</div>
@endsection
