<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('checkout.pos.title', ['name' => $merchant->name]) }}</title>
    <link rel="icon" href="{{ asset('assets/crynova/favicon/favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('assets/crynova/favicon/apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $currencyOptions = $currencies->values();
    $selectedCurrencyId = (string) old('currency_id', optional($currencyOptions->first())->id);
    $currencyCodeById = $currencyOptions
        ->mapWithKeys(fn ($currency) => [(string) $currency->id => $currency->code])
        ->all();
    $selectedCurrencyCode = $currencyCodeById[$selectedCurrencyId] ?? ($merchant->base_currency_code ?: 'USD');
    $merchantInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($merchant->name ?: 'C', 0, 1));
    $merchantLogo = $merchant->logo_path
        ? (str_starts_with($merchant->logo_path, 'http') ? $merchant->logo_path : asset('storage/'.$merchant->logo_path))
        : null;
@endphp
<body class="min-h-screen bg-[#f7f9fc] text-slate-950">
<main class="relative isolate min-h-screen overflow-hidden px-4 py-6 sm:px-6 lg:px-8">
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[28rem] bg-[radial-gradient(circle_at_50%_0%,rgba(37,99,235,0.12),transparent_34rem)]"></div>
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 bottom-0 -z-10 h-96 bg-[linear-gradient(to_top,rgba(226,232,240,0.72),transparent)]"></div>

    <div class="mx-auto max-w-5xl">
        <header class="mb-6 flex items-center justify-between rounded-[1.5rem] border border-slate-200 bg-white/92 px-4 py-3 shadow-lg shadow-slate-200/60 backdrop-blur sm:px-5">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3">
                <x-logo variant="mark" class="h-10 w-10 rounded-2xl shadow-md shadow-blue-600/20" />
                <span class="text-lg font-black tracking-tight text-slate-950">Crynova</span>
            </a>
            <div class="inline-flex items-center gap-2 rounded-2xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700">
                <x-icon name="shield-check" class="h-4 w-4" />
                {{ __('checkout.secure_badge') }}
            </div>
        </header>

        <section class="grid gap-5 lg:grid-cols-[0.95fr_1.05fr] lg:items-start">
            <aside class="space-y-5">
                <section class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/65">
                    <div class="flex items-start gap-4">
                        @if($merchantLogo)
                            <img src="{{ $merchantLogo }}" alt="{{ $merchant->name }}" class="h-16 w-16 rounded-2xl border border-slate-200 bg-white object-cover shadow-lg shadow-slate-200/80">
                        @else
                            <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-2xl font-black text-white shadow-xl shadow-blue-600/24">
                                {{ $merchantInitial }}
                            </span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <span class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                                <x-icon name="link" class="h-3.5 w-3.5" />
                                {{ __('checkout.pos.permanent_page') }}
                            </span>
                            <h1 class="mt-3 truncate text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                                {{ $merchant->name }}
                            </h1>
                            @if($merchant->website || $merchant->domain)
                                <p class="mt-2 truncate text-sm font-semibold text-slate-500">{{ $merchant->website ?: $merchant->domain }}</p>
                            @endif
                        </div>
                    </div>

                    <p class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm leading-6 text-slate-600">
                        {{ $merchant->project_description ? \Illuminate\Support\Str::limit($merchant->project_description, 170) : __('checkout.pos.desc_fallback') }}
                    </p>

                    <div class="mt-5 space-y-2.5">
                        <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                <x-icon name="qr" class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-sm font-black text-slate-950">{{ __('checkout.pos.feat_checkout') }}</p>
                                <p class="mt-1 text-sm leading-5 text-slate-500">{{ __('checkout.pos.feat_checkout_text') }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                <x-icon name="bell" class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-sm font-black text-slate-950">{{ __('checkout.pos.feat_live') }}</p>
                                <p class="mt-1 text-sm leading-5 text-slate-500">{{ __('checkout.pos.feat_live_text') }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/60">
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                <x-icon name="coins" class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-base font-black tracking-tight text-slate-950">{{ __('checkout.pos.available') }}</h2>
                                <p class="mt-0.5 text-xs font-medium text-slate-500">{{ __('checkout.pos.available_text') }}</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">{{ $currencyOptions->count() }}</span>
                    </div>

                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        @forelse($currencyOptions->take(8) as $currency)
                            @php($network = strtoupper((string) $currency->network))
                            <div class="flex min-h-[4.75rem] items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3.5 py-3 shadow-sm shadow-slate-200/50">
                                <x-coin-icon :code="$currency->code" class="h-9 w-9" />
                                <div class="min-w-0 flex-1">
                                    <p class="break-words text-sm font-black leading-5 text-slate-950">{{ $currency->code }}</p>
                                    <p class="mt-0.5 break-words text-[0.7rem] font-bold uppercase leading-4 tracking-[0.08em] text-slate-400">{{ $network }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                                {{ __('checkout.pos.unavailable') }}
                            </div>
                        @endforelse
                    </div>
                </section>
            </aside>

            <section
                class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-2xl shadow-slate-200/70 sm:p-7"
                x-data="{
                    mode: @js(old('fiat_currency') ? 'fiat' : 'crypto'),
                    fiatCurrency: @js(old('fiat_currency', $fiatCurrencies[0] ?? 'USD')),
                    selectedCurrency: @js($selectedCurrencyId),
                    currencyCodes: @js($currencyCodeById),
                    fallbackCurrency: @js($selectedCurrencyCode),
                    get selectedCurrencyCode() {
                        return this.currencyCodes[this.selectedCurrency] || this.fallbackCurrency;
                    },
                    get amountCurrency() {
                        return this.mode === 'fiat' ? this.fiatCurrency : this.selectedCurrencyCode;
                    }
                }"
            >
                <div class="mb-6 border-b border-slate-200 pb-5">
                    <p class="text-sm font-bold text-blue-700">Crynova Payment</p>
                    <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950">{{ __('checkout.pos.create_invoice') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ __('checkout.pos.create_text') }}</p>
                </div>

                @if(session('error'))
                    <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ session('error') }}</div>
                @endif

                <form method="POST" action="{{ route('checkout.pos.create', $merchant->shop_id) }}" class="space-y-6">
                    @csrf
                    <div>
                        <label class="fin-label" for="amount">{{ __('checkout.pos.amount') }} <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input id="amount" name="amount" type="number" step="any" min="0.00000001" required class="fin-input min-h-14 pr-32 text-2xl font-black tracking-tight @error('amount') border-rose-500 @enderror" value="{{ old('amount') }}" placeholder="0.00">
                            <span class="pointer-events-none absolute right-4 top-1/2 max-w-[7.5rem] -translate-y-1/2 truncate rounded-xl bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500" x-text="amountCurrency">
                                {{ $selectedCurrencyCode }}
                            </span>
                        </div>
                        @error('amount')<p class="mt-2 text-xs font-medium text-rose-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Pay-in mode toggle: price in fiat (customer picks crypto) or directly in crypto --}}
                    @if(!empty($fiatCurrencies))
                        <div class="inline-flex rounded-2xl border border-slate-200 bg-slate-50 p-1">
                            <button type="button" @click="mode='fiat'" :class="mode==='fiat' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500'" class="rounded-xl px-4 py-2 text-sm font-bold transition">{{ __('checkout.pos.in_fiat') }}</button>
                            <button type="button" @click="mode='crypto'" :class="mode==='crypto' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500'" class="rounded-xl px-4 py-2 text-sm font-bold transition">{{ __('checkout.pos.in_crypto') }}</button>
                        </div>

                        {{-- Fiat: customer chooses the crypto on the next step --}}
                        <div x-show="mode==='fiat'" x-cloak>
                            <label class="fin-label" for="fiat_currency">{{ __('checkout.pos.fiat_currency') }} <span class="text-rose-500">*</span></label>
                            <select id="fiat_currency" name="fiat_currency" x-model="fiatCurrency" :disabled="mode!=='fiat'" class="fin-input min-h-14 text-lg font-bold">
                                @foreach($fiatCurrencies as $code)
                                    <option value="{{ $code }}">{{ $code }}</option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-slate-500">{{ __('checkout.pos.fiat_hint') }}</p>
                        </div>
                    @endif

                    <div x-show="mode==='crypto'" @if(!empty($fiatCurrencies)) x-cloak @endif>
                        <div class="mb-3 flex items-center justify-between gap-4">
                            <label class="fin-label mb-0">{{ __('checkout.pos.currency') }} <span class="text-rose-500">*</span></label>
                            <span class="text-xs font-semibold text-slate-400">{{ __('checkout.pos.currency_network') }}</span>
                        </div>

                        <div class="grid max-h-[25rem] gap-3 overflow-y-auto pr-1 sm:grid-cols-2">
                            @foreach($currencyOptions as $currency)
                                @php($network = strtoupper((string) $currency->network))
                                <label class="group cursor-pointer" @click="selectedCurrency = @js((string) $currency->id)">
                                    <input
                                        type="radio"
                                        name="currency_id"
                                        value="{{ $currency->id }}"
                                        class="sr-only"
                                        :disabled="mode!=='crypto'"
                                        x-model="selectedCurrency"
                                        @checked((string) $currency->id === $selectedCurrencyId)
                                    >
                                    <span
                                        :class="selectedCurrency === @js((string) $currency->id) ? 'border-blue-500 bg-blue-50 shadow-lg shadow-blue-600/10 ring-4 ring-blue-100' : 'border-slate-200 bg-white hover:border-blue-200 hover:bg-slate-50'"
                                        class="flex min-h-[6.25rem] items-center gap-3 rounded-2xl border p-4 transition"
                                    >
                                        <x-coin-icon :code="$currency->code" class="h-11 w-11" />
                                        <span class="min-w-0 flex-1">
                                            <span class="block break-words text-sm font-black leading-5 text-slate-950">{{ $currency->code }}</span>
                                            <span class="mt-1 block break-words text-xs font-medium leading-4 text-slate-500">{{ $currency->name }}</span>
                                            <span class="mt-2 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[0.68rem] font-bold uppercase tracking-[0.08em] text-slate-500">{{ $network }}</span>
                                        </span>
                                        <span
                                            :class="selectedCurrency === @js((string) $currency->id) ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-300'"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full transition"
                                        >
                                            <x-icon name="check" class="h-4 w-4" />
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('currency_id')<p class="mt-2 text-xs font-medium text-rose-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <div class="flex gap-3">
                            <x-icon name="alert-triangle" class="mt-0.5 h-5 w-5 shrink-0" />
                            <p>{{ __('checkout.pos.note') }}</p>
                        </div>
                    </div>

                    <button type="submit" class="group flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 py-4 text-sm font-black text-white shadow-xl shadow-blue-600/25 transition hover:-translate-y-0.5 hover:shadow-2xl hover:shadow-blue-600/30 active:translate-y-0">
                        {{ __('checkout.pos.pay') }}
                        <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                    </button>
                </form>

                <p class="mt-5 text-center text-xs text-slate-400">
                    {{ __('checkout.pos.secured') }} <a href="{{ url('/') }}" class="font-semibold text-blue-600 hover:underline">Crynova</a> · {{ __('checkout.pos.crypto_payments') }}
                </p>
            </section>
        </section>
    </div>
</main>
</body>
</html>
