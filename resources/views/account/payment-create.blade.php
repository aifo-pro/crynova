@extends('layouts.app')
@section('title', __('account.payments.create_title'))

@section('content')
@php
    $currencyOptions = $currencies->values();
    $selectedCurrencyId = (string) old('currency_id', optional($currencyOptions->first())->id);
    $selectedCurrency = $currencyOptions->firstWhere('id', (int) $selectedCurrencyId) ?? $currencyOptions->first();
    $selectedProjectId = (string) old('merchant_id', optional($projects->first())->id);
    $selectedProject = $projects->firstWhere('id', (int) $selectedProjectId) ?? $projects->first();

    // Fiat code → [name, ISO-3166 country code for the flag (flagcdn.com)].
    $fiatNames = [
        'USD'=>['US Dollar','us'], 'EUR'=>['Euro','eu'], 'GBP'=>['British Pound','gb'], 'JPY'=>['Japanese Yen','jp'],
        'CNY'=>['Chinese Yuan','cn'], 'RUB'=>['Russian Ruble','ru'], 'INR'=>['Indian Rupee','in'], 'AUD'=>['Australian Dollar','au'],
        'CAD'=>['Canadian Dollar','ca'], 'SGD'=>['Singapore Dollar','sg'], 'HKD'=>['Hong Kong Dollar','hk'], 'TRY'=>['Turkish Lira','tr'],
        'AED'=>['UAE Dirham','ae'], 'THB'=>['Thai Baht','th'], 'MYR'=>['Malaysian Ringgit','my'], 'PHP'=>['Philippine Peso','ph'],
        'IDR'=>['Indonesian Rupiah','id'], 'VND'=>['Vietnamese Dong','vn'], 'KZT'=>['Kazakhstani Tenge','kz'], 'UAH'=>['Ukrainian Hryvnia','ua'],
        'BYN'=>['Belarusian Ruble','by'], 'UZS'=>['Uzbekistani Som','uz'], 'KGS'=>['Kyrgyzstani Som','kg'], 'AMD'=>['Armenian Dram','am'],
        'AZN'=>['Azerbaijani Manat','az'], 'PLN'=>['Polish Zloty','pl'],
    ];
    $fiatJs = [];
    foreach ($fiatCurrencies as $fc) {
        $fiatJs[$fc] = ['name' => $fiatNames[$fc][0] ?? $fc, 'flag' => $fiatNames[$fc][1] ?? ''];
    }
    $fiatNonePh = __('account.payments.fiat_none');
@endphp

<div
    class="space-y-6"
    x-data="{
        amount: @js(old('amount', '')),
        currencyId: @js($selectedCurrencyId),
        currencyCode: @js($selectedCurrency?->code ?? ''),
        fiatCurrency: @js(old('fiat_currency', '')),
        fiatMeta: @js($fiatJs),
        openFiat: false,
        projectId: @js($selectedProjectId),
        projectName: @js($selectedProject?->name ?? ''),
        setCurrency(id, code) {
            this.currencyId = String(id);
            this.currencyCode = code;
            this.fiatCurrency = '';
        },
        setFiat(code) {
            this.fiatCurrency = code;
            this.openFiat = false;
        }
    }"
>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <x-badge variant="blue">{{ __('account.payments.create_badge') }}</x-badge>
            <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950">{{ __('account.payments.create_title') }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ __('account.payments.create_subtitle') }}</p>
        </div>
        <x-button href="{{ route('account.payments') }}" variant="secondary" icon="arrow-left">
            {{ __('account.payments.back_to_payments') }}
        </x-button>
    </div>

    @if($projects->isEmpty())
        <section class="rounded-[1.75rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                <x-icon name="credit-card" class="h-6 w-6" />
            </span>
            <h2 class="mt-5 text-xl font-black text-slate-950">{{ __('account.payments.no_active_projects') }}</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">{{ __('account.payments.no_active_projects_text') }}</p>
            <x-button href="{{ route('account.projects') }}" class="mt-6" icon="plus">{{ __('account.payments.to_projects') }}</x-button>
        </section>
    @else
        <form method="POST" action="{{ route('account.payments.store') }}" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_23rem]">
            @csrf

            <section class="rounded-[1.75rem] border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">{{ __('account.payments.choose_params') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('account.payments.choose_params_text') }}</p>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-2xl bg-slate-50 px-3 py-2 text-xs font-bold text-slate-500 ring-1 ring-slate-200">
                        <x-icon name="shield-check" class="h-4 w-4 text-blue-600" />
                        {{ __('account.payments.secure_checkout') }}
                    </span>
                </div>

                <div class="space-y-6 p-5">
                    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,0.7fr)]">
                        <div>
                            <label class="fin-label" for="amount">{{ __('account.payments.amount_to_pay') }}</label>
                            <div class="relative">
                                <input id="amount" name="amount" type="number" step="any" min="0.00000001" x-model="amount" class="fin-input min-h-14 pr-24 text-2xl font-black tracking-tight @error('amount') border-rose-500 @enderror" placeholder="0.00" required>
                                <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 rounded-xl bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500" x-text="fiatCurrency || currencyCode || '—'"></span>
                            </div>
                            @error('amount')<p class="mt-2 text-xs font-medium text-rose-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="fin-label" for="merchant_id">{{ __('account.payments.choose_project') }}</label>
                            <x-project-select
                                name="merchant_id"
                                :projects="$projects"
                                :selected="$selectedProjectId"
                                :placeholder="__('account.payments.select_project')"
                                sync-id="projectId"
                                sync-name="projectName"
                                required
                            />
                            @error('merchant_id')<p class="mt-2 text-xs font-medium text-rose-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="fin-label">{{ __('account.payments.fiat_label') }}</label>
                        <div class="relative" @click.outside="openFiat = false">
                            <input type="hidden" name="fiat_currency" :value="fiatCurrency">
                            <button type="button" @click="openFiat = !openFiat"
                                    class="fin-input flex min-h-14 w-full items-center justify-between gap-2 text-left">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <template x-if="fiatCurrency">
                                        <img :src="`https://flagcdn.com/w40/${fiatMeta[fiatCurrency]?.flag}.png`" alt="" class="h-5 w-7 shrink-0 rounded object-cover ring-1 ring-slate-200">
                                    </template>
                                    <template x-if="!fiatCurrency">
                                        <span class="grid h-5 w-7 shrink-0 place-items-center rounded bg-slate-100 text-slate-400"><x-icon name="coins" class="h-3.5 w-3.5" /></span>
                                    </template>
                                    <span class="truncate font-semibold text-slate-800" x-text="fiatCurrency ? (fiatCurrency + ' · ' + (fiatMeta[fiatCurrency]?.name || '')) : @js($fiatNonePh)"></span>
                                </span>
                                <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="openFiat && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </button>

                            <div x-show="openFiat" x-cloak x-transition.origin.top
                                 class="absolute left-0 right-0 z-30 mt-2 max-h-72 overflow-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl shadow-slate-300/40">
                                <button type="button" @click="setFiat('')"
                                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition hover:bg-slate-50"
                                        :class="!fiatCurrency && 'bg-blue-50'">
                                    <span class="grid h-5 w-7 shrink-0 place-items-center rounded bg-slate-100 text-slate-400"><x-icon name="coins" class="h-3.5 w-3.5" /></span>
                                    <span class="font-semibold text-slate-700">{{ __('account.payments.fiat_none') }}</span>
                                </button>
                                @foreach($fiatJs as $code => $meta)
                                    <button type="button" @click="setFiat('{{ $code }}')"
                                            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition hover:bg-slate-50"
                                            :class="fiatCurrency === '{{ $code }}' && 'bg-blue-50'">
                                        <img src="https://flagcdn.com/w40/{{ $meta['flag'] }}.png" alt="{{ $code }}" loading="lazy" class="h-5 w-7 shrink-0 rounded object-cover ring-1 ring-slate-200">
                                        <span class="font-bold text-slate-950">{{ $code }}</span>
                                        <span class="truncate text-slate-400">{{ $meta['name'] }}</span>
                                        <span class="ml-auto" x-show="fiatCurrency === '{{ $code }}'"><x-icon name="check" class="h-4 w-4 text-blue-600" /></span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">{{ __('account.payments.fiat_hint') }}</p>
                    </div>

                    <div :class="fiatCurrency ? 'pointer-events-none opacity-40' : ''">
                        <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                            <label class="fin-label mb-0">{{ __('account.balance.currency') }} <span class="text-slate-400" x-show="!fiatCurrency">({{ __('account.payments.or_direct') }})</span></label>
                            <span class="text-xs font-semibold text-slate-400">{{ __('account.payments.currency_hint') }}</span>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            @foreach($currencyOptions as $currency)
                                @php
                                    $network = match (true) {
                                        str_contains($currency->code, 'ERC20') => 'ERC-20',
                                        str_contains($currency->code, 'TRC20') => 'TRC-20',
                                        str_contains($currency->code, 'BEP20') => 'BEP-20',
                                        default => strtoupper((string) $currency->network),
                                    };
                                @endphp
                                <label class="group cursor-pointer" @click="setCurrency(@js((string) $currency->id), @js($currency->code))">
                                    <input
                                        type="radio"
                                        name="currency_id"
                                        value="{{ $currency->id }}"
                                        class="sr-only"
                                        :required="!fiatCurrency"
                                        :disabled="!!fiatCurrency"
                                        @checked((string) $currency->id === $selectedCurrencyId)
                                    >
                                    <span
                                        :class="currencyId === @js((string) $currency->id) ? 'border-blue-500 bg-blue-50 shadow-lg shadow-blue-600/10 ring-4 ring-blue-100' : 'border-slate-200 bg-white hover:border-blue-200 hover:bg-slate-50'"
                                        class="relative flex min-h-[5.35rem] items-center gap-3 rounded-2xl border p-4 pr-12 transition"
                                    >
                                        <x-coin-icon :code="$currency->code" class="h-11 w-11" />
                                        <span class="min-w-0 flex-1">
                                            <span class="block whitespace-nowrap font-mono text-sm font-black text-slate-950">{{ $currency->code }}</span>
                                            <span class="mt-1 block truncate text-xs font-medium text-slate-500">{{ $currency->name }}</span>
                                            <span class="mt-2 inline-flex rounded-full bg-white px-2.5 py-1 text-[0.68rem] font-bold uppercase tracking-[0.08em] text-slate-500 ring-1 ring-slate-200">{{ $network }}</span>
                                        </span>
                                        <span
                                            :class="currencyId === @js((string) $currency->id) ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-300'"
                                            class="absolute right-3 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full transition"
                                        >
                                            <x-icon name="check" class="h-4 w-4" />
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('currency_id')<p class="mt-2 text-xs font-medium text-rose-500">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <aside class="space-y-4">
                <section class="rounded-[1.75rem] border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-lg font-black text-slate-950">{{ __('account.payments.invoice_preview') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('account.payments.review_subtitle') }}</p>
                    </div>

                    <div class="space-y-4 p-5">
                        <div class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 via-white to-cyan-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-600">INV-NEW</p>
                            <p class="mt-3 text-3xl font-black tracking-tight text-slate-950">
                                <span x-text="amount || '0'"></span>
                                <span class="text-blue-600" x-text="fiatCurrency || currencyCode"></span>
                            </p>
                            <p class="mt-2 truncate text-sm font-semibold text-slate-500" x-text="projectName || @js(__('account.payments.select_project'))"></p>
                            <p x-show="fiatCurrency" x-cloak class="mt-2 text-xs leading-5 text-blue-700">{{ __('account.payments.fiat_preview') }}</p>
                        </div>

                        <div class="grid gap-3">
                            <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                    <x-icon name="qr" class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="text-sm font-black text-slate-950">{{ __('account.payments.hosted_checkout') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ __('account.payments.hosted_checkout_text') }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                    <x-icon name="bell" class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="text-sm font-black text-slate-950">{{ __('account.payments.live_status') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ __('account.payments.live_status_text') }}</p>
                                </div>
                            </div>
                        </div>

                        <x-button type="submit" icon="credit-card" class="w-full rounded-2xl">
                            {{ __('account.payments.create_invoice_button') }}
                        </x-button>
                    </div>
                </section>

                <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-black text-slate-950">{{ __('account.payments.available_assets') }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ __('account.payments.available_assets_text') }}</p>
                        </div>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                            <x-icon name="coins" class="h-5 w-5" />
                        </span>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($currencyOptions->take(8) as $currency)
                            <span class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700">
                                <x-coin-icon :code="$currency->code" class="h-6 w-6" />
                                {{ $currency->code }}
                            </span>
                        @endforeach
                    </div>
                </section>
            </aside>
        </form>
    @endif
</div>

@if(session('created_invoice'))
    @php $ci = session('created_invoice'); @endphp
    <script src="https://unpkg.com/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js"></script>
    <div x-data="{
            open: true, copied: false, qr: null,
            init() {
                this.qr = new QRCodeStyling({
                    width: 132, height: 132, type: 'svg',
                    data: @js($ci['url']),
                    image: '{{ asset('assets/crynova/icon-logo.png') }}',
                    margin: 4,
                    qrOptions: { errorCorrectionLevel: 'H' },
                    dotsOptions: { color: '#1e293b', type: 'rounded' },
                    cornersSquareOptions: { type: 'extra-rounded', color: '#2563eb' },
                    cornersDotOptions: { color: '#2563eb' },
                    backgroundOptions: { color: '#ffffff' },
                    imageOptions: { crossOrigin: 'anonymous', margin: 5, imageSize: 0.32 },
                });
                this.$nextTick(() => { this.$refs.qrbox.innerHTML = ''; this.qr.append(this.$refs.qrbox); });
            },
            download() { this.qr && this.qr.download({ name: 'crynova-qr', extension: 'png' }); }
         }" x-show="open" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="open=false"></div>
        <div x-show="open" x-transition class="relative max-h-[92vh] w-full max-w-lg overflow-auto rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl sm:p-7">
            <div class="flex items-start justify-between gap-4">
                <h2 class="text-2xl font-black text-slate-950">{{ __('account.payments.created_title') }}</h2>
                <button type="button" @click="open=false" class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200"><x-icon name="x" class="h-4 w-4" /></button>
            </div>
            <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('account.payments.created_text', ['amount' => $ci['amount'], 'currency' => $ci['currency']]) }}</p>
            <p class="text-sm text-slate-500">{{ __('account.payments.created_valid', ['hours' => $ci['expires_hours']]) }}</p>

            {{-- QR + link --}}
            <div class="mt-5 flex flex-col gap-4 rounded-2xl bg-slate-50 p-4 sm:flex-row sm:items-center">
                <div x-ref="qrbox" class="mx-auto h-[132px] w-[132px] shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-white p-1 sm:mx-0"></div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm leading-6 text-slate-500">{{ __('account.payments.created_scan') }}</p>
                    <div class="mt-2 flex items-center gap-2">
                        <a href="{{ $ci['url'] }}" target="_blank" rel="noopener" class="truncate text-sm font-semibold text-blue-600 hover:underline">{{ $ci['url'] }}</a>
                        <button type="button" @click="navigator.clipboard.writeText('{{ $ci['url'] }}'); copied=true; setTimeout(()=>copied=false,1500)" class="shrink-0 text-slate-400 hover:text-blue-600"><x-icon name="copy" class="h-4 w-4" /></button>
                        <span x-show="copied" x-cloak class="text-xs font-semibold text-emerald-600">✓</span>
                    </div>
                    <button type="button" @click="download()" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-blue-600"><x-icon name="arrow-right" class="h-4 w-4 rotate-90" /> {{ __('account.payments.created_download') }}</button>
                </div>
            </div>

            {{-- Details --}}
            <div class="mt-5 grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div><p class="text-xs text-slate-400">{{ __('account.payments.created_time') }}</p><p class="mt-0.5 font-bold text-slate-900">{{ str_pad((string) $ci['expires_hours'], 2, '0', STR_PAD_LEFT) }}:00:00</p></div>
                <div><p class="text-xs text-slate-400">{{ __('account.payments.created_project') }}</p><p class="mt-0.5 font-bold text-slate-900">{{ $ci['project'] }}</p></div>
                <div><p class="text-xs text-slate-400">{{ __('account.payments.created_transfer') }}</p><p class="mt-0.5 font-bold text-slate-900">{{ $ci['transfer_payer'] }}</p></div>
                <div><p class="text-xs text-slate-400">{{ __('account.payments.created_service') }}</p><p class="mt-0.5 font-bold text-slate-900">{{ $ci['service_payer'] }}</p></div>
            </div>

            {{-- Available methods --}}
            @if(!empty($ci['methods']))
                <p class="mt-5 text-xs font-semibold text-slate-400">{{ __('account.payments.created_methods') }}</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach($ci['methods'] as $m)
                        <x-coin-icon :code="$m" class="h-7 w-7" />
                    @endforeach
                </div>
            @endif

            <a href="{{ $ci['url'] }}" target="_blank" rel="noopener" class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 py-3 text-sm font-bold text-white hover:opacity-90">
                {{ __('account.payments.created_open') }} <x-icon name="arrow-right" class="h-4 w-4" />
            </a>
        </div>
    </div>
@endif
@endsection
