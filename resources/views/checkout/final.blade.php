<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('checkout.invoice') }} #{{ substr($invoice->uuid, 0, 8) }} - Crynova</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $formatCrypto = function ($value): string {
        $value = (string) $value;
        if (str_contains($value, '.')) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        return $value === '' ? '0' : $value;
    };

    $currency = $invoice->currency;
    $merchant = $invoice->merchant;
    $currencyCode = $currency?->code ?? 'CRYPTO';
    $networkLabel = $currency?->network
        ? \Illuminate\Support\Str::of($currency->network)->replace('_', ' ')->upper()
        : $currencyCode;
    $merchantName = $merchant?->name ?: 'Crynova';
    $merchantInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($merchantName, 0, 1));
    $expectedAmount = $formatCrypto($invoice->payableAmount());
    $receivedAmount = $formatCrypto($invoice->amount_received);

    $rawMerchantUrl = $merchant?->website ?: $merchant?->domain;
    $merchantUrl = $rawMerchantUrl
        ? (preg_match('/^https?:\/\//i', $rawMerchantUrl) ? $rawMerchantUrl : 'https://'.$rawMerchantUrl)
        : null;
    $retryUrl = ($merchant && $merchant->shop_id && $merchant->featuresUnlocked())
        ? route('checkout.pos', $merchant->shop_id)
        : null;

    $statusMap = [
        'paid' => [
            'icon' => 'check',
            'label' => __('checkout.payment_confirmed'),
            'title' => __('checkout.final.paid_title'),
            'text' => __('checkout.final.paid_text'),
            'iconClass' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
            'badgeClass' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'surfaceClass' => 'from-emerald-50 via-white to-cyan-50',
            'barClass' => 'bg-emerald-500',
        ],
        'underpaid' => [
            'icon' => 'alert-triangle',
            'label' => __('checkout.underpaid'),
            'title' => __('checkout.final.underpaid_title'),
            'text' => __('checkout.final.underpaid_text'),
            'iconClass' => 'bg-amber-50 text-amber-600 ring-amber-100',
            'badgeClass' => 'border-amber-200 bg-amber-50 text-amber-700',
            'surfaceClass' => 'from-amber-50 via-white to-orange-50',
            'barClass' => 'bg-amber-500',
        ],
        'overpaid' => [
            'icon' => 'wallet',
            'label' => __('checkout.overpaid'),
            'title' => __('checkout.final.overpaid_title'),
            'text' => __('checkout.final.overpaid_text'),
            'iconClass' => 'bg-cyan-50 text-cyan-600 ring-cyan-100',
            'badgeClass' => 'border-cyan-200 bg-cyan-50 text-cyan-700',
            'surfaceClass' => 'from-cyan-50 via-white to-blue-50',
            'barClass' => 'bg-cyan-500',
        ],
        'expired' => [
            'icon' => 'clock',
            'label' => __('checkout.invoice_expired'),
            'title' => __('checkout.final.expired_title'),
            'text' => __('checkout.final.expired_text'),
            'iconClass' => 'bg-amber-50 text-amber-600 ring-amber-100',
            'badgeClass' => 'border-amber-200 bg-amber-50 text-amber-700',
            'surfaceClass' => 'from-amber-50 via-white to-slate-50',
            'barClass' => 'bg-amber-500',
        ],
        'failed' => [
            'icon' => 'x',
            'label' => __('checkout.payment_failed'),
            'title' => __('checkout.final.failed_title'),
            'text' => __('checkout.final.failed_text'),
            'iconClass' => 'bg-rose-50 text-rose-600 ring-rose-100',
            'badgeClass' => 'border-rose-200 bg-rose-50 text-rose-700',
            'surfaceClass' => 'from-rose-50 via-white to-slate-50',
            'barClass' => 'bg-rose-500',
        ],
        'refunded' => [
            'icon' => 'arrow-right',
            'label' => __('checkout.refunded'),
            'title' => __('checkout.final.refunded_title'),
            'text' => __('checkout.final.refunded_text'),
            'iconClass' => 'bg-slate-100 text-slate-600 ring-slate-200',
            'badgeClass' => 'border-slate-200 bg-slate-100 text-slate-700',
            'surfaceClass' => 'from-slate-50 via-white to-blue-50',
            'barClass' => 'bg-slate-500',
        ],
    ];
    $status = $statusMap[$invoice->status] ?? [
        'icon' => 'bell',
        'label' => ucfirst(str_replace('_', ' ', $invoice->status)),
        'title' => ucfirst(str_replace('_', ' ', $invoice->status)),
        'text' => __('checkout.final.default_text'),
        'iconClass' => 'bg-blue-50 text-blue-600 ring-blue-100',
        'badgeClass' => 'border-blue-200 bg-blue-50 text-blue-700',
        'surfaceClass' => 'from-blue-50 via-white to-cyan-50',
        'barClass' => 'bg-blue-500',
    ];
@endphp
<body class="min-h-screen bg-[#f7f9fc] text-slate-950 antialiased">
<main class="relative isolate min-h-screen overflow-hidden px-4 py-6 sm:px-6 lg:px-8">
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[26rem] bg-[radial-gradient(circle_at_50%_0%,rgba(37,99,235,0.13),transparent_32rem)]"></div>
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 bottom-0 -z-10 h-96 bg-[linear-gradient(to_top,rgba(226,232,240,0.78),transparent)]"></div>

    <div class="mx-auto flex min-h-[calc(100vh-3rem)] max-w-5xl flex-col justify-center">
        <header class="mb-5 flex flex-col gap-3 rounded-[1.5rem] border border-slate-200 bg-white/95 px-4 py-3 shadow-lg shadow-slate-200/70 backdrop-blur sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3">
                <x-logo variant="mark" class="h-11 w-11 rounded-2xl shadow-md shadow-blue-600/20" />
                <span>
                    <span class="block text-base font-black tracking-tight text-slate-950">Crynova Checkout</span>
                    <span class="block text-xs font-semibold text-slate-500">{{ __('checkout.final.brand_subtitle') }}</span>
                </span>
            </a>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-bold text-slate-700">
                    <x-coin-icon :code="$currencyCode" class="h-6 w-6" />
                    {{ $currencyCode }}
                </span>
                <span class="inline-flex items-center gap-2 rounded-2xl border border-blue-100 bg-blue-50 px-3 py-1.5 text-sm font-bold text-blue-700">
                    <x-icon name="shield-check" class="h-4 w-4" />
                    {{ __('checkout.final.protected') }}
                </span>
            </div>
        </header>

        <section class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl shadow-slate-200/80">
                <div class="h-1.5 {{ $status['barClass'] }}"></div>
                <div class="bg-gradient-to-br {{ $status['surfaceClass'] }} p-5 sm:p-7">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-4">
                                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl ring-1 {{ $status['iconClass'] }}">
                                    <x-icon :name="$status['icon']" class="h-8 w-8" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">{{ __('checkout.final.status') }}</p>
                                    <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">{{ $status['title'] }}</h1>
                                </div>
                            </div>
                            <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">{{ $status['text'] }}</p>
                        </div>
                        <span class="inline-flex w-fit shrink-0 items-center rounded-full border px-3.5 py-1.5 text-xs font-black {{ $status['badgeClass'] }}">
                            {{ $status['label'] }}
                        </span>
                    </div>

                    @if($invoice->status === 'expired')
                        <div class="mt-6 rounded-2xl border border-amber-200 bg-white/85 p-4 text-amber-800 shadow-sm">
                            <div class="flex gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                                    <x-icon name="alert-triangle" class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="font-black">{{ __('checkout.final.expired_notice_title') }}</p>
                                    <p class="mt-1 text-sm leading-6">{{ __('checkout.final.expired_notice_text') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <div class="min-w-0 rounded-[1.35rem] border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">{{ __('checkout.final.expected_amount') }}</span>
                                <x-coin-icon :code="$currencyCode" class="h-8 w-8" />
                            </div>
                            <p class="mt-3 break-all font-mono text-2xl font-black tracking-tight text-slate-950">
                                {{ $expectedAmount }} <span class="text-blue-600">{{ $currencyCode }}</span>
                            </p>
                        </div>
                        <div class="min-w-0 rounded-[1.35rem] border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">{{ __('checkout.final.received_amount') }}</span>
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                    <x-icon name="wallet" class="h-4 w-4" />
                                </span>
                            </div>
                            <p class="mt-3 break-all font-mono text-2xl font-black tracking-tight text-slate-950">
                                {{ $receivedAmount }} <span class="text-slate-500">{{ $currencyCode }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 rounded-[1.35rem] border border-slate-200 bg-white/90 p-4">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-base font-black text-white shadow-lg shadow-blue-600/25">
                                    {{ $merchantInitial }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-slate-950">{{ $merchantName }}</p>
                                    <p class="mt-0.5 truncate text-sm font-medium text-slate-500">{{ __('checkout.invoice') }} #{{ substr($invoice->uuid, 0, 8) }}</p>
                                </div>
                            </div>
                            <div class="inline-flex w-fit items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-black text-slate-700">
                                <x-coin-icon :code="$currencyCode" class="h-6 w-6" />
                                {{ $networkLabel }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                        @if($invoice->status === 'expired' && $retryUrl)
                            <x-button href="{{ $retryUrl }}" icon="arrow-right" class="w-full sm:w-auto">
                                {{ __('checkout.final.create_new_invoice') }}
                            </x-button>
                        @endif
                        @if($merchantUrl)
                            <x-button href="{{ $merchantUrl }}" variant="secondary" class="w-full sm:w-auto">
                                {{ __('checkout.final.return_to_merchant') }}
                            </x-button>
                        @else
                            <x-button href="{{ url('/') }}" variant="secondary" class="w-full sm:w-auto">
                                {{ __('checkout.final.back_home') }}
                            </x-button>
                        @endif
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
                <section class="rounded-[2rem] border border-slate-200 bg-white shadow-xl shadow-slate-200/75">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-base font-black text-slate-950">{{ __('checkout.final.payment_summary') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('checkout.final.reference') }} #{{ substr($invoice->uuid, 0, 8) }}</p>
                    </div>
                    <dl class="space-y-3 p-5 text-sm">
                        <div class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 px-4 py-3">
                            <dt class="font-medium text-slate-500">{{ __('checkout.final.merchant') }}</dt>
                            <dd class="min-w-0 truncate text-right font-black text-slate-950">{{ $merchantName }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 px-4 py-3">
                            <dt class="font-medium text-slate-500">{{ __('checkout.final.network') }}</dt>
                            <dd class="min-w-0 truncate text-right font-black text-slate-950">{{ $networkLabel }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 px-4 py-3">
                            <dt class="font-medium text-slate-500">{{ __('checkout.final.status') }}</dt>
                            <dd class="text-right font-black text-slate-950">{{ $status['label'] }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 px-4 py-3">
                            <dt class="font-medium text-slate-500">{{ __('checkout.final.created') }}</dt>
                            <dd class="text-right font-mono text-xs font-black text-slate-950">{{ $invoice->created_at?->format('Y-m-d H:i') }}</dd>
                        </div>
                        @if($invoice->expires_at)
                            <div class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 px-4 py-3">
                                <dt class="font-medium text-slate-500">{{ __('checkout.final.expired_at') }}</dt>
                                <dd class="text-right font-mono text-xs font-black text-slate-950">{{ $invoice->expires_at->format('Y-m-d H:i') }}</dd>
                            </div>
                        @endif
                        @if($invoice->paid_at)
                            <div class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 px-4 py-3">
                                <dt class="font-medium text-slate-500">{{ __('checkout.final.paid_at') }}</dt>
                                <dd class="text-right font-mono text-xs font-black text-slate-950">{{ $invoice->paid_at->format('Y-m-d H:i') }}</dd>
                            </div>
                        @endif
                        @if($invoice->order_id)
                            <div class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 px-4 py-3">
                                <dt class="font-medium text-slate-500">{{ __('checkout.final.order') }}</dt>
                                <dd class="min-w-0 break-all text-right font-mono text-xs font-black text-slate-950">{{ $invoice->order_id }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>

                @if($invoice->status === 'expired')
                    <section class="rounded-[2rem] border border-amber-200 bg-amber-50 p-5 text-amber-900 shadow-lg shadow-amber-100/80">
                        <div class="flex gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white text-amber-600 shadow-sm">
                                <x-icon name="clock" class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="font-black">{{ __('checkout.final.no_more_payments') }}</h2>
                                <p class="mt-2 text-sm leading-6">{{ __('checkout.final.secure_note') }}</p>
                            </div>
                        </div>
                    </section>
                @endif

                <p class="text-center text-xs font-semibold text-slate-400">
                    {{ __('checkout.powered_by') }}
                </p>
            </aside>
        </section>
    </div>
</main>
</body>
</html>
