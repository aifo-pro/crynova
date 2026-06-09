@extends('layouts.app')
@section('title', __('merchant_settings.constructor.title', ['name' => $merchant->name]))

@section('content')
<div class="space-y-6"
     x-data="{
        theme: '{{ $config['theme'] }}',
        order: '{{ $config['currency_order'] }}',
        lang: '{{ $config['language'] }}',
        reset(){ this.theme='classic'; this.order='default'; this.lang='auto'; },
        themeBg(){ return {classic:'bg-white', minimal:'bg-slate-50', dark:'bg-slate-900', gradient:'bg-gradient-to-br from-blue-50 to-violet-50'}[this.theme]; },
        themeText(){ return this.theme==='dark' ? 'text-white' : 'text-slate-950'; },
     }">
    @include('merchant.settings._tabs')
    <form method="POST" action="{{ route('merchant.settings.constructor.update', $merchant) }}"
          class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @csrf
        <input type="hidden" name="theme" :value="theme">
        <input type="hidden" name="currency_order" :value="order">
        <input type="hidden" name="language" :value="lang">

        <div class="mb-6 flex items-center gap-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><x-icon name="layout" class="h-5 w-5" /></span>
            <h2 class="text-lg font-semibold text-slate-950">{{ __('merchant_settings.constructor.heading') }}</h2>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Controls --}}
            <div class="rounded-2xl border border-slate-200 p-5">
                <h3 class="mb-4 font-semibold text-slate-950">{{ __('merchant_settings.constructor.params') }}</h3>

                <div class="space-y-5">
                    <div>
                        <label class="fin-label">{{ __('merchant_settings.constructor.theme') }}</label>
                        <select x-model="theme" class="fin-input">
                            <option value="classic">classic</option>
                            <option value="minimal">minimal</option>
                            <option value="dark">dark</option>
                            <option value="gradient">gradient</option>
                        </select>
                    </div>

                    <div>
                        <label class="fin-label">{{ __('merchant_settings.constructor.order') }}</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" @click="order='default'" :class="order==='default' ? 'border-blue-500 ring-2 ring-blue-500/20 text-blue-600' : 'border-slate-200 text-slate-600'" class="rounded-xl border px-4 py-2.5 text-sm font-semibold">{{ __('merchant_settings.constructor.default') }}</button>
                            <button type="button" @click="order='custom'" :class="order==='custom' ? 'border-blue-500 ring-2 ring-blue-500/20 text-blue-600' : 'border-slate-200 text-slate-600'" class="rounded-xl border px-4 py-2.5 text-sm font-semibold">{{ __('merchant_settings.constructor.custom') }}</button>
                        </div>
                    </div>

                    <div>
                        <label class="fin-label">{{ __('merchant_settings.constructor.language') }}</label>
                        <select x-model="lang" class="fin-input">
                            <option value="auto">{{ __('merchant_settings.constructor.auto') }}</option>
                            <option value="en">English</option>
                            <option value="uk">Українська</option>
                        </select>
                    </div>

                    <button type="button" @click="reset()" class="text-sm font-semibold text-blue-600 hover:underline">{{ __('merchant_settings.common.reset') }}</button>
                </div>
            </div>

            {{-- Live preview --}}
            <div class="rounded-2xl border border-slate-200 p-5">
                <div class="rounded-2xl border border-slate-200 p-4 transition-colors" :class="themeBg()">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-blue-600 text-[10px] font-black text-white">C</span>
                            <span class="text-sm font-bold" :class="themeText()">Crynova</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">23:59:45</span>
                            <span class="text-xs" :class="themeText()" x-text="lang==='auto' ? 'EN' : lang.toUpperCase()"></span>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-slate-950">INV-DEMO</span>
                            <span class="text-xs text-slate-400">{{ $merchant->name }}</span>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-sm text-slate-500">Amount to pay</span>
                            <span class="font-semibold text-slate-950">251.4 USDT</span>
                        </div>
                        <div class="mt-4">
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Select payment method</label>
                            <div class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-[9px] font-bold text-white">T</span>
                                <span class="text-sm font-semibold text-slate-800">USDT</span>
                                <x-icon name="chevron-down" class="ml-auto h-4 w-4 text-slate-400" />
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Select network</label>
                            <div class="flex items-center gap-2 rounded-xl border border-blue-300 px-3 py-2.5">
                                <span class="text-sm font-semibold text-slate-800">TRC20</span>
                                <x-icon name="chevron-down" class="ml-auto h-4 w-4 text-slate-400" />
                            </div>
                        </div>
                        <button type="button" class="mt-4 w-full rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white">Proceed to payment</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end"><x-button type="submit" class="rounded-full px-8">{{ __('merchant_settings.common.save') }}</x-button></div>
    </form>
</div>
@endsection
