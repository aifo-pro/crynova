@extends('layouts.app')
@section('title', __('account.merchant_create.title'))

@section('content')
<div class="mx-auto max-w-4xl"
     x-data="{
        step: 1,
        accept: '{{ old('accept_type', 'website') }}',
        desc: @js(old('project_description', '')),
        tos: {{ old('accept_tos') ? 'true' : 'false' }},
        currencies: @js(collect(old('currencies', []))->map(fn($v) => (int) $v)->all()),
        toggle(id) {
            const i = this.currencies.indexOf(id);
            if (i === -1) this.currencies.push(id); else this.currencies.splice(i, 1);
        },
        has(id) { return this.currencies.includes(id); },
        get descLen() { return this.desc.length; },
        get canNext() { return this.accept && this.currencies.length > 0 && this.descLen >= 100; },
     }">
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('account.dashboard') }}" class="text-slate-400 hover:text-blue-600"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <div>
            <h1 class="text-3xl font-semibold text-slate-950">{{ __('account.merchant_create.title') }}</h1>
            <p class="mt-1 text-slate-500">{{ __('account.merchant_create.subtitle') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('account.merchants.store') }}" novalidate>
        @csrf
        <input type="hidden" name="accept_type" :value="accept">
        <template x-for="cid in currencies" :key="cid">
            <input type="hidden" name="currencies[]" :value="cid">
        </template>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div x-show="step === 1" class="space-y-8">
                <div class="grid gap-6 sm:grid-cols-[1fr_1.4fr] sm:items-start">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">{{ __('account.merchant_create.accept_title') }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ __('account.merchant_create.accept_text') }}</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <button type="button" @click="accept='website'" :class="accept === 'website' ? 'border-blue-500 bg-blue-50/60 ring-2 ring-blue-500/20' : 'border-slate-200 hover:border-blue-200'" class="rounded-2xl border px-4 py-4 text-left text-sm font-semibold text-slate-700 transition">{{ __('account.merchant_create.accept_website') }}</button>
                        <button type="button" @click="accept='donation'" :class="accept === 'donation' ? 'border-blue-500 bg-blue-50/60 ring-2 ring-blue-500/20' : 'border-slate-200 hover:border-blue-200'" class="rounded-2xl border px-4 py-4 text-left text-sm font-semibold text-slate-700 transition">{{ __('account.merchant_create.accept_donation') }}</button>
                    </div>
                </div>

                <hr class="border-slate-100">

                <div class="grid gap-6 sm:grid-cols-[1fr_1.4fr] sm:items-start">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">{{ __('account.merchant_create.name_title') }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ __('account.merchant_create.name_text') }}</p>
                    </div>
                    <input name="name" type="text" minlength="3" maxlength="60" required value="{{ old('name') }}" class="fin-input" placeholder="{{ __('account.merchant_create.name_placeholder') }}">
                </div>

                <hr class="border-slate-100">

                <div class="grid gap-6 sm:grid-cols-[1fr_1.4fr] sm:items-start">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">{{ __('account.merchant_create.business_title') }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ __('account.merchant_create.business_text') }}</p>
                    </div>
                    <select name="business_type" required class="fin-input">
                        <option value="">{{ __('account.merchant_create.business.digital_goods') }}</option>
                        @foreach($businessTypes as $val => $label)
                            <option value="{{ $val }}" @selected(old('business_type') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <hr class="border-slate-100">

                <div class="grid gap-6 sm:grid-cols-[1fr_1.4fr] sm:items-start">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">{{ __('account.merchant_create.description_title') }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ __('account.merchant_create.description_text') }}</p>
                    </div>
                    <div>
                        <textarea name="project_description" x-model="desc" rows="5" minlength="100" maxlength="250" required class="fin-input" placeholder="{{ __('account.merchant_create.description_placeholder') }}"></textarea>
                        <div class="mt-1 flex justify-between text-xs">
                            <div class="text-slate-400">
                                <p>{{ __('account.merchant_create.min_chars') }}</p>
                                <p>{{ __('account.merchant_create.max_chars') }}</p>
                            </div>
                            <span :class="descLen < 100 ? 'text-rose-400' : 'text-emerald-500'" x-text="descLen"></span>
                        </div>
                    </div>
                </div>

                <hr class="border-slate-100">

                <div class="grid gap-6 sm:grid-cols-[1fr_1.4fr] sm:items-start">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">{{ __('account.merchant_create.currencies_title') }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ __('account.merchant_create.currencies_text') }}</p>
                    </div>
                    <div class="space-y-6 rounded-2xl border border-slate-200 p-4">
                        @foreach($grouped as $group)
                            <div>
                                <p class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-600">
                                    <x-icon :name="$group['icon']" class="h-4 w-4 text-slate-400" /> {{ $group['label'] }}
                                </p>
                                <div class="grid gap-3 sm:grid-cols-3">
                                    @foreach($group['items'] as $c)
                                        <button type="button" @click="toggle({{ $c->id }})" :class="has({{ $c->id }}) ? 'border-blue-500 bg-blue-50/60 ring-2 ring-blue-500/20' : 'border-slate-200 hover:border-blue-200'" class="flex items-center gap-2 rounded-xl border px-3 py-2.5 text-left transition">
                                            <x-coin-icon :code="$c->code" class="h-7 w-7" />
                                            <span class="text-sm font-semibold text-slate-800">{{ explode('_', $c->code)[0] }}</span>
                                            @if(str_contains($c->code, '_') || $c->network)
                                                <span class="ml-auto rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-semibold uppercase text-slate-400">{{ \Illuminate\Support\Str::after($c->code, '_') ?: $c->network }}</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        <p class="text-xs" :class="currencies.length ? 'text-emerald-500' : 'text-slate-400'">
                            {{ __('account.merchant_create.selected_currencies') }} <span x-text="currencies.length"></span>
                        </p>
                    </div>
                </div>
            </div>

            <div x-show="step === 2" x-cloak class="space-y-8">
                <div x-show="accept === 'donation'" class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700">{{ __('account.merchant_create.donation_notice') }}</div>

                <div class="grid gap-6 sm:grid-cols-[1fr_1.4fr] sm:items-start">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">{{ __('account.merchant_create.website_title') }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ __('account.merchant_create.website_text') }}</p>
                    </div>
                    <input name="domain" type="text" value="{{ old('domain') }}" class="fin-input" placeholder="https://domain.com/">
                </div>

                <hr class="border-slate-100">

                <div class="grid gap-6 sm:grid-cols-[1fr_1.4fr] sm:items-start">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">{{ __('account.merchant_create.cms_title') }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ __('account.merchant_create.cms_text') }}</p>
                    </div>
                    <select name="cms" class="fin-input">
                        <option value="">{{ __('account.merchant_create.choose_cms') }}</option>
                        @foreach($cmsList as $cms)
                            <option value="{{ $cms }}" @selected(old('cms') === $cms)>{{ $cms }}</option>
                        @endforeach
                    </select>
                </div>

                <hr class="border-slate-100">

                @foreach([
                    ['success_url', __('account.merchant_create.success_url_title'), __('account.merchant_create.success_url_text'), 'https://domain.com/successful-payment'],
                    ['fail_url', __('account.merchant_create.fail_url_title'), __('account.merchant_create.fail_url_text'), 'https://domain.com/failed-payment'],
                    ['callback_url', __('account.merchant_create.callback_url_title'), __('account.merchant_create.callback_url_text'), 'https://domain.com/callback'],
                ] as [$name, $title, $text, $placeholder])
                    <div class="grid gap-6 sm:grid-cols-[1fr_1.4fr] sm:items-start">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-950">{{ $title }}</h2>
                            <p class="mt-2 text-sm text-slate-500">{{ $text }}</p>
                        </div>
                        <input name="{{ $name }}" type="url" value="{{ old($name) }}" class="fin-input" placeholder="{{ $placeholder }}">
                    </div>
                    @if(!$loop->last)<hr class="border-slate-100">@endif
                @endforeach

                <hr class="border-slate-100">

                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50/60 p-4 transition hover:border-blue-200">
                    <input type="checkbox" name="accept_tos" value="1" x-model="tos" class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-blue-600">
                    <span class="text-sm leading-6 text-slate-700">
                        {!! __('account.merchant_create.tos_label', ['url' => url('/tos')]) !!}
                    </span>
                </label>
                @error('accept_tos')<p class="-mt-4 text-xs font-medium text-rose-500">{{ $message }}</p>@enderror
            </div>

            <hr class="my-6 border-slate-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-slate-400">
                    <span x-text="step"></span>/2
                    <span class="flex gap-1">
                        <span class="h-1.5 w-1.5 rounded-full" :class="step === 1 ? 'bg-blue-600' : 'bg-slate-300'"></span>
                        <span class="h-1.5 w-1.5 rounded-full" :class="step === 2 ? 'bg-blue-600' : 'bg-slate-300'"></span>
                    </span>
                </div>

                <div class="flex gap-3">
                    <button type="button" x-show="step === 2" @click="step = 1" class="text-sm font-semibold text-slate-500 hover:text-slate-900">← {{ __('account.merchant_create.back') }}</button>
                    <button type="button" x-show="step === 1" @click="canNext ? step = 2 : null" :class="canNext ? 'bg-blue-600 hover:bg-blue-700' : 'cursor-not-allowed bg-slate-300'" class="rounded-full px-8 py-3 text-sm font-semibold text-white transition">{{ __('account.merchant_create.next') }}</button>
                    <button type="submit" x-show="step === 2" :disabled="!tos" :class="tos ? 'bg-blue-600 hover:bg-blue-700' : 'cursor-not-allowed bg-slate-300'" class="rounded-full px-8 py-3 text-sm font-semibold text-white transition">{{ __('account.merchant_create.submit') }}</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
