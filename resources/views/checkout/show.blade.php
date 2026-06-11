<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('checkout.pay_with', ['currency' => $invoice->currency->name]) }} - Crynova</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $formatCrypto = function ($value) {
        $value = rtrim(rtrim((string) $value, '0'), '.');
        return $value === '' ? '0' : $value;
    };

    // Compact display: truncate to 8 decimals for readable summaries. The exact
    // full-precision amount stays in the "exact amount" copy field below.
    $formatCompact = function ($value) {
        $v = rtrim(rtrim(bcadd((string) $value, '0', 8), '0'), '.');
        return $v === '' || $v === '-0' ? '0' : $v;
    };

    $baseAmount = $formatCompact($invoice->amount);
    $transferFee = $formatCompact($invoice->transferFee());
    $hasTransferFee = bccomp((string) $invoice->transferFee(), '0', 18) > 0;
    $payable = $invoice->payableAmount();
    // "$amount" is the figure the customer must actually send (amount + transfer fee).
    $amount = $formatCrypto($payable);            // full precision — authoritative
    $amountCompact = $formatCompact($payable);    // compact — for visual summaries
    $receivedAmount = $formatCompact($invoice->amount_received);
    $currencyCode = $invoice->currency->code;
    $networkLabel = strtoupper((string) $invoice->currency->network);
    $requiredConfirmations = max(1, (int) $invoice->currency->confirmations_required);
    $currentConfirmations = $invoice->status === 'paid'
        ? $requiredConfirmations
        : min($requiredConfirmations, max(0, (int) $invoice->transactions->max('confirmations')));
    $confirmationProgress = min(100, round(($currentConfirmations / $requiredConfirmations) * 100));
    $merchantInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($invoice->merchant->name ?: 'C', 0, 1));

    $statusLabels = [
        'pending' => __('checkout.status.pending'),
        'waiting_confirmations' => __('checkout.status.waiting_confirmations'),
        'processing' => __('checkout.status.processing'),
        'paid' => __('checkout.status.paid'),
        'underpaid' => __('checkout.status.underpaid'),
        'overpaid' => __('checkout.status.overpaid'),
        'expired' => __('checkout.status.expired'),
        'failed' => __('checkout.status.failed'),
        'refunded' => __('checkout.status.refunded'),
    ];

    $statusClasses = [
        'pending' => 'border-amber-200 bg-amber-50 text-amber-700',
        'waiting_confirmations' => 'border-blue-200 bg-blue-50 text-blue-700',
        'processing' => 'border-blue-200 bg-blue-50 text-blue-700',
        'paid' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'underpaid' => 'border-amber-200 bg-amber-50 text-amber-700',
        'overpaid' => 'border-cyan-200 bg-cyan-50 text-cyan-700',
        'expired' => 'border-slate-200 bg-slate-100 text-slate-600',
        'failed' => 'border-rose-200 bg-rose-50 text-rose-700',
        'refunded' => 'border-slate-200 bg-slate-100 text-slate-600',
    ];
@endphp
<body class="min-h-screen bg-[#f7f9fc] text-slate-950">
<main class="relative isolate min-h-screen overflow-hidden px-4 py-5 sm:px-6 lg:px-8">
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[26rem] bg-[radial-gradient(circle_at_50%_0%,rgba(37,99,235,0.12),transparent_32rem)]"></div>
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 bottom-0 -z-10 h-96 bg-[linear-gradient(to_top,rgba(226,232,240,0.75),transparent)]"></div>

    <div class="mx-auto max-w-5xl">
        <header class="mb-5 flex flex-col gap-3 rounded-[1.5rem] border border-slate-200 bg-white/92 px-4 py-3 shadow-lg shadow-slate-200/60 backdrop-blur sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3">
                <x-logo variant="mark" class="h-10 w-10 rounded-2xl shadow-md shadow-blue-600/20" />
                <span>
                    <span class="block text-base font-black tracking-tight text-slate-950">Crynova Checkout</span>
                    <span class="block text-xs font-semibold text-slate-500">{{ __('checkout.secure_payment') }}</span>
                </span>
            </a>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-bold text-slate-700">
                    <x-coin-icon :code="$currencyCode" class="h-6 w-6" />
                    {{ $currencyCode }}
                </span>
                <span class="inline-flex items-center gap-2 rounded-2xl border border-blue-100 bg-blue-50 px-3 py-1.5 text-sm font-bold text-blue-700">
                    <x-icon name="shield-check" class="h-4 w-4" />
                    {{ __('checkout.secure_badge') }}
                </span>
                <x-language-switcher compact />
            </div>
        </header>

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_21rem]">
            <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/70">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-base font-black text-blue-700">
                                {{ $merchantInitial }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-500">{{ $invoice->merchant->name }}</p>
                                <p class="mt-0.5 text-xs font-medium text-slate-400">{{ __('checkout.invoice') }} #{{ substr($invoice->uuid, 0, 8) }}</p>
                            </div>
                        </div>
                        @if($invoice->description)
                            <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-500">{{ $invoice->description }}</p>
                        @endif
                    </div>
                    <span id="status-badge" class="inline-flex shrink-0 items-center justify-center rounded-full border px-3.5 py-1.5 text-xs font-black {{ $statusClasses[$invoice->status] ?? $statusClasses['pending'] }}">
                        {{ $statusLabels[$invoice->status] ?? ucfirst(str_replace('_', ' ', $invoice->status)) }}
                    </span>
                </div>

                <div class="mt-5 rounded-[1.35rem] border border-blue-100 bg-gradient-to-br from-blue-50 via-white to-cyan-50 p-4">
                    <p class="text-sm font-bold text-blue-700">{{ __('checkout.amount_due') }}</p>
                    <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <h1 class="text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                            {{ $amountCompact }}
                            <span class="text-blue-600">{{ $currencyCode }}</span>
                        </h1>
                        <div class="inline-flex w-fit items-center gap-2 rounded-2xl bg-white px-3 py-2 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-200">
                            <x-coin-icon :code="$currencyCode" class="h-7 w-7" />
                            {{ $networkLabel }}
                        </div>
                    </div>
                </div>

                <div class="mt-5 grid gap-5 xl:grid-cols-[15rem_minmax(0,1fr)]">
                    <div class="mx-auto w-full max-w-[15rem] xl:mx-0">
                        <div class="rounded-[1.5rem] border border-slate-200 bg-white p-3 shadow-lg shadow-slate-200/70">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($qrData) }}" alt="Payment QR" width="220" height="220" class="block w-full rounded-2xl">
                        </div>
                        <p class="mt-3 text-center text-sm font-medium text-slate-500">{{ __('checkout.scan_wallet') }}</p>
                    </div>

                    <div class="min-w-0 space-y-4">
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800">
                            <div class="flex gap-3">
                                <x-icon name="alert-triangle" class="mt-0.5 h-5 w-5 shrink-0" />
                                <div>
                                    <p class="font-bold">{{ __('checkout.exact_title') }}</p>
                                    <p class="mt-1 text-sm leading-6">{{ __('checkout.exact_text', ['amount' => $amount, 'currency' => $currencyCode, 'network' => $invoice->currency->network]) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 grid gap-4">
                    <div class="min-w-0">
                        <label class="fin-label">{{ __('checkout.exact_amount') }}</label>
                        <div class="flex min-w-0 items-stretch gap-2">
                            <code id="pay-amount" class="flex min-h-12 min-w-0 flex-1 items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 font-mono text-sm font-bold leading-5 text-slate-950 break-all whitespace-normal">
                                {{ $amount }} {{ $currencyCode }}
                            </code>
                            <x-copy-button target="pay-amount" class="h-auto min-h-12 w-12 shrink-0" />
                        </div>
                    </div>

                    <x-crypto-address-box id="pay-address" :label="__('checkout.wallet_address')" :value="$invoice->pay_address" />

                    @if($invoice->pay_memo)
                        <x-crypto-address-box id="pay-memo" :label="__('checkout.memo')" :value="$invoice->pay_memo" />
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">{{ __('checkout.memo_required') }}</div>
                    @endif
                </div>
            </section>

            <aside class="space-y-4">
                <section class="rounded-[1.75rem] border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <h2 class="text-base font-black text-slate-950">{{ __('checkout.live_status') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('checkout.live_status_subtitle') }}</p>
                    </div>
                    <div class="space-y-3 p-4">
                        @if($invoice->expires_at)
                            <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="flex items-center gap-2 text-sm font-bold text-blue-700">
                                        <x-icon name="clock" class="h-4 w-4" />
                                        {{ __('checkout.time_left') }}
                                    </span>
                                    <span id="countdown" class="font-mono text-xl font-black text-slate-950">--:--</span>
                                </div>
                            </div>
                        @endif

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="text-sm font-medium text-slate-500">{{ __('checkout.network') }}</span>
                                    <span class="text-sm font-black text-slate-950">{{ $networkLabel }}</span>
                                </div>
                            </div>

                            @if($hasTransferFee)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="min-w-0 text-sm font-medium text-slate-500">{{ __('checkout.invoice_amount') }}</span>
                                        <span class="shrink-0 whitespace-nowrap font-mono text-sm font-semibold text-slate-700">{{ $baseAmount }} {{ $currencyCode }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="min-w-0 text-sm font-medium text-slate-500">{{ __('checkout.transfer_fee') }}</span>
                                        <span class="shrink-0 whitespace-nowrap font-mono text-sm font-semibold text-amber-600">+ {{ $transferFee }} {{ $currencyCode }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 border-t border-slate-200 pt-2">
                                        <span class="min-w-0 text-sm font-bold text-slate-700">{{ __('checkout.total_due') }}</span>
                                        <span class="shrink-0 whitespace-nowrap font-mono text-sm font-black text-blue-700">{{ $amountCompact }} {{ $currencyCode }}</span>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="min-w-0 text-sm font-medium text-slate-500">{{ __('checkout.received') }}</span>
                                    <span id="amount-received" class="shrink-0 whitespace-nowrap text-right font-mono text-sm font-black text-slate-950">{{ $receivedAmount }} {{ $currencyCode }}</span>
                                </div>
                            </div>
                        </div>

                        @if($hasTransferFee)
                            <p class="flex items-start gap-2 px-1 text-xs leading-5 text-slate-400">
                                <x-icon name="alert-triangle" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                {{ __('checkout.transfer_fee_note') }}
                            </p>
                        @endif

                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="mb-3 flex items-center justify-between gap-4">
                                <span class="text-sm font-bold text-slate-700">{{ __('checkout.confirmations') }}</span>
                                <span class="font-mono text-sm font-black text-slate-950">
                                    <span id="confirmations-current">{{ $currentConfirmations }}</span> / {{ $requiredConfirmations }}
                                </span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                <div id="confirmations-progress" class="h-full rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 transition-all duration-500" style="width: {{ $confirmationProgress }}%"></div>
                            </div>
                            <p class="mt-3 text-xs font-medium text-slate-500">{{ __('checkout.confirm_note') }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-[1.75rem] border border-slate-200 bg-white shadow-lg shadow-slate-200/60">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <h2 class="text-base font-black text-slate-950">{{ __('checkout.payment_details') }}</h2>
                    </div>
                    <dl class="space-y-3 p-4 text-sm">
                        @if($invoice->order_id)
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">{{ __('checkout.order_id') }}</dt>
                                <dd class="text-right font-semibold text-slate-950">{{ $invoice->order_id }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">{{ __('checkout.created') }}</dt>
                            <dd class="text-right font-semibold text-slate-950">{{ $invoice->created_at->format('Y-m-d H:i') }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">{{ __('checkout.required_confirmations') }}</dt>
                            <dd class="text-right font-semibold text-slate-950">{{ $requiredConfirmations }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Webhook</dt>
                            <dd class="text-right font-semibold {{ $invoice->webhook_delivered ? 'text-emerald-700' : 'text-slate-500' }}">
                                {{ $invoice->webhook_delivered ? 'Delivered' : 'Waiting' }}
                            </dd>
                        </div>
                    </dl>
                </section>
            </aside>
        </div>
    </div>
</main>

<script>
    @if($invoice->expires_at)
    const expiresAt = new Date('{{ $invoice->expires_at->toIso8601String() }}');
    let expiredHandled = false;
    function updateCountdown() {
        const diff = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
        const m = String(Math.floor(diff / 60)).padStart(2, '0');
        const s = String(diff % 60).padStart(2, '0');
        document.getElementById('countdown').textContent = `${m}:${s}`;

        // Time is up — reload so the server marks the invoice expired and shows
        // the "invoice expired" page (with the Fail URL button).
        if (diff <= 0 && !expiredHandled) {
            expiredHandled = true;
            setTimeout(() => location.reload(), 1200);
        }
    }
    updateCountdown();
    setInterval(updateCountdown, 1000);
    @endif

    const statusUrl = '{{ route('checkout.status', $invoice->uuid) }}';
    const statusLabels = @json($statusLabels);
    const requiredConfirmations = {{ $requiredConfirmations }};
    const badge = document.getElementById('status-badge');
    const received = document.getElementById('amount-received');
    const confirmationsCurrent = document.getElementById('confirmations-current');
    const confirmationsProgress = document.getElementById('confirmations-progress');

    function formatCrypto(value) {
        let raw = String(value ?? '0');
        // Truncate to 8 decimals for a readable display (matches server-side summary).
        if (raw.includes('.')) {
            const [int, dec] = raw.split('.');
            raw = dec.length > 8 ? `${int}.${dec.slice(0, 8)}` : raw;
            raw = raw.replace(/0+$/, '').replace(/\.$/, '') || '0';
        }
        return raw;
    }

    async function pollStatus() {
        try {
            const res = await fetch(statusUrl);
            const data = await res.json();
            const label = statusLabels[data.status] || data.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

            if (badge) {
                badge.textContent = label;
            }

            if (received) {
                received.textContent = `${formatCrypto(data.amount_received)} {{ $currencyCode }}`;
            }

            if (confirmationsCurrent && confirmationsProgress && data.confirmations !== undefined) {
                const required = Number(data.confirmations_required || requiredConfirmations || 1);
                const current = Math.min(required, Math.max(0, Number(data.confirmations || 0)));
                confirmationsCurrent.textContent = current;
                confirmationsProgress.style.width = `${Math.min(100, Math.round((current / Math.max(required, 1)) * 100))}%`;
            }

            if (data.is_final) {
                location.reload();
                return;
            }
        } catch (e) {}
        setTimeout(pollStatus, 5000);
    }
    setTimeout(pollStatus, 5000);
</script>
</body>
</html>
