@extends('layouts.app')
@section('title', __('merchant_settings.widget.title', ['name' => $merchant->name]))

@php
    $appUrl = rtrim(config('app.url'), '/');
    $widgetUrl = $appUrl.'/pay/pos/'.$merchant->shop_id;
    $widgetState = [
        'type' => $config['type'] ?? 'button',
        'template' => $config['template'] ?? 'cc-paywith',
        'text' => $config['text'] ?? 'Pay with crypto',
        'style' => $config['style'] ?? 'dark',
        'size' => $config['size'] ?? 'standard',
        'amount' => $config['amount'] ?? '10',
        'currency' => $config['currency'] ?? 'USD',
        'lang' => in_array($config['language'] ?? 'uk', ['ua', 'ru'], true) ? 'uk' : ($config['language'] ?? 'uk'),
        'shopId' => $merchant->shop_id,
        'appUrl' => $appUrl,
    ];
@endphp

@section('content')
<div class="space-y-6" x-data="crynovaWidgetBuilder(@js($widgetState))">
    @include('merchant.settings._tabs')
    <form method="POST" action="{{ route('merchant.settings.widget.update', $merchant) }}"
          class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @csrf
        <input type="hidden" name="type" :value="type">
        <input type="hidden" name="template" :value="template">
        <input type="hidden" name="text" :value="text">
        <input type="hidden" name="style" :value="style">
        <input type="hidden" name="size" :value="size">
        <input type="hidden" name="amount" :value="amount">
        <input type="hidden" name="currency" :value="currency">
        <input type="hidden" name="language" :value="lang">

        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><x-icon name="layout" class="h-5 w-5" /></span>
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">{{ __('merchant_settings.widget.heading') }}</h2>
                    <p class="text-sm text-slate-500">{{ $merchant->name }}</p>
                </div>
            </div>
            <a href="{{ $widgetUrl }}" target="_blank" class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                <x-icon name="external-link" class="h-4 w-4" />
                {{ $widgetUrl }}
            </a>
        </div>

        <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="rounded-2xl border border-slate-200 p-5">
                <h3 class="mb-4 font-semibold text-slate-950">{{ __('merchant_settings.widget.params') }}</h3>

                <div class="space-y-4">
                    <div>
                        <label class="fin-label">{{ __('merchant_settings.widget.type') }}</label>
                        <select x-model="type" class="fin-input">
                            <option value="button">{{ __('merchant_settings.widget.button') }}</option>
                            <option value="inline">{{ __('merchant_settings.widget.inline') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="fin-label">{{ __('merchant_settings.widget.template') }}</label>
                        <select x-model="template" class="fin-input">
                            <option value="cc-paywith">cc-paywith</option>
                            <option value="cc-simple">cc-simple</option>
                            <option value="cc-badge">cc-badge</option>
                        </select>
                    </div>

                    <div>
                        <label class="fin-label">{{ __('merchant_settings.widget.button_text') }}</label>
                        <input x-model="text" type="text" maxlength="40" class="fin-input" placeholder="Pay with crypto">
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="fin-label">{{ __('merchant_settings.widget.style') }}</label>
                            <select x-model="style" class="fin-input">
                                <option value="dark">{{ __('merchant_settings.widget.dark') }}</option>
                                <option value="light">{{ __('merchant_settings.widget.light') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="fin-label">{{ __('merchant_settings.widget.size') }}</label>
                            <select x-model="size" class="fin-input">
                                <option value="standard">{{ __('merchant_settings.widget.standard') }}</option>
                                <option value="compact">{{ __('merchant_settings.widget.compact') }}</option>
                                <option value="large">{{ __('merchant_settings.widget.large') }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="fin-label">{{ __('merchant_settings.widget.amount') }}</label>
                        <div class="flex gap-2">
                            <input x-model="amount" type="number" step="any" min="0" class="fin-input flex-1" placeholder="10">
                            <select x-model="currency" class="fin-input w-32">
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                @foreach(\App\Models\Currency::where('is_active', true)->orderBy('code')->pluck('code') as $code)
                                    <option value="{{ $code }}">{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="fin-label">{{ __('merchant_settings.widget.language') }}</label>
                        <select x-model="lang" class="fin-input">
                            <option value="uk">Українська</option>
                            <option value="en">English</option>
                        </select>
                    </div>

                    <button type="button" @click="reset()" class="text-sm font-semibold text-blue-600 hover:underline">{{ __('merchant_settings.common.reset') }}</button>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="font-semibold text-slate-950">Preview</h3>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500 shadow-sm" x-text="template + ' · ' + size"></span>
                    </div>

                    <div class="flex min-h-48 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white p-6">
                        <template x-if="type === 'button'">
                            <button type="button"
                                    :class="buttonClass"
                                    class="inline-flex items-center gap-2 rounded-xl font-semibold shadow transition">
                                <span x-text="btnText"></span>
                                <span class="flex -space-x-1">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-orange-400 text-[9px] font-bold text-white">B</span>
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-500 text-[9px] font-bold text-white">E</span>
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-[9px] font-bold text-white">T</span>
                                </span>
                            </button>
                        </template>

                        <template x-if="type === 'inline'">
                            <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-950" x-text="btnText"></span>
                                    <span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-600" x-text="currency"></span>
                                </div>
                                <div class="rounded-xl border border-slate-200 px-3 py-2.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-slate-500">Amount</span>
                                        <span class="font-semibold text-slate-950" x-text="amount + ' ' + currency"></span>
                                    </div>
                                </div>
                                <button type="button" :class="buttonClass" class="mt-3 w-full rounded-xl font-semibold shadow transition">
                                    <span x-text="btnText"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="font-semibold text-slate-950">Embed code</h3>
                        <button type="button" @click="copySnippet($event)" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white">Copy</button>
                    </div>
                    <pre class="max-h-80 overflow-auto whitespace-pre-wrap break-all rounded-2xl bg-slate-950 p-4 text-xs leading-relaxed text-slate-200"><code x-text="snippet"></code></pre>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <x-button type="submit" class="rounded-full px-8">{{ __('merchant_settings.common.save') }}</x-button>
        </div>
    </form>
</div>

<script>
    window.crynovaWidgetBuilder = function(initial) {
        return {
            type: initial.type,
            template: initial.template,
            text: initial.text,
            style: initial.style,
            size: initial.size,
            amount: initial.amount,
            currency: initial.currency,
            lang: initial.lang,
            shopId: initial.shopId,
            appUrl: initial.appUrl,
            reset() {
                this.type = 'button';
                this.template = 'cc-paywith';
                this.text = 'Pay with crypto';
                this.style = 'dark';
                this.size = 'standard';
                this.amount = '10';
                this.currency = 'USD';
                this.lang = 'uk';
            },
            get btnText() {
                return this.text || 'Pay with crypto';
            },
            get buttonClass() {
                return [
                    this.style === 'dark' ? 'bg-slate-900 text-white hover:bg-slate-800' : 'border border-slate-300 bg-white text-slate-900 hover:bg-slate-50',
                    this.size === 'compact' ? 'px-3 py-2 text-xs' : '',
                    this.size === 'standard' ? 'px-5 py-2.5 text-sm' : '',
                    this.size === 'large' ? 'px-7 py-3.5 text-base' : '',
                ].join(' ');
            },
            get snippet() {
                const amount = Number(this.amount || 0);
                return [
                    '<button class="cc-payment-button"></button>',
                    '<script defer src="' + this.appUrl + '/widget/v1/widget.js"><\\/script>',
                    '<script>',
                    '  document.addEventListener("DOMContentLoaded", function() {',
                    '    if (window.CrynovaWidget) {',
                    '      window.CrynovaWidget.createInvoiceButton({',
                    '        size: "' + this.size + '",',
                    '        template: "' + this.template + ':' + this.style + '",',
                    '        text: "' + this.btnText.replaceAll('"', '\\"') + '",',
                    '        amount: ' + amount + ',',
                    '        currency: "' + this.currency + '",',
                    '        shop_id: "' + this.shopId + '",',
                    '        lang: "' + this.lang + '"',
                    '      });',
                    '    }',
                    '  });',
                    '<\\/script>',
                ].join('\n');
            },
            async copySnippet(event) {
                await navigator.clipboard.writeText(this.snippet);
                const button = event.currentTarget;
                const original = button.textContent;
                button.textContent = 'OK';
                setTimeout(() => button.textContent = original, 1200);
            },
        };
    };
</script>
@endsection
