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
<body class="min-h-screen bg-slate-50 px-4 py-6 text-slate-950 sm:py-10 dark:bg-slate-950 dark:text-white">
<main class="mx-auto max-w-6xl">
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <x-logo variant="mark" />
            <div>
                <p class="font-semibold">Crynova Checkout</p>
                <p class="text-xs text-slate-500">{{ __('checkout.secure_payment') }}</p>
            </div>
        </div>
        <button type="button" data-theme-toggle class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
            <x-icon name="sparkles" class="h-4 w-4" />
        </button>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-2xl shadow-slate-200/70 dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-black/20 sm:p-8">
            <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-start sm:justify-between dark:border-slate-800">
                <div>
                    <p class="text-sm text-slate-500">{{ $invoice->merchant->name }}</p>
                    <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950 dark:text-white">
                        {{ number_format((float) $invoice->amount, 8, '.', '') }}
                        <span class="text-blue-600">{{ $invoice->currency->code }}</span>
                    </h1>
                    @if($invoice->description)
                        <p class="mt-2 text-sm text-slate-500">{{ $invoice->description }}</p>
                    @endif
                </div>
                <x-status-badge id="status-badge" :status="$invoice->status" />
            </div>

            <div class="mt-8 grid gap-8 lg:grid-cols-[15rem_1fr]">
                <div>
                    <div class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-xl shadow-slate-200/70 dark:border-slate-800">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data={{ urlencode($qrData) }}" alt="Payment QR" width="240" height="240" class="block w-full rounded-xl">
                    </div>
                    <p class="mt-4 text-center text-sm text-slate-500">{{ __('checkout.scan_wallet') }}</p>
                </div>

                <div class="space-y-5">
                    <x-alert variant="warning" :title="__('checkout.exact_title')">
                        {{ __('checkout.exact_text', ['amount' => number_format((float) $invoice->amount, 8, '.', ''), 'currency' => $invoice->currency->code, 'network' => $invoice->currency->network]) }}
                    </x-alert>

                    <x-crypto-address-box id="pay-address" :label="__('checkout.wallet_address')" :value="$invoice->pay_address" />

                    @if($invoice->pay_memo)
                        <x-crypto-address-box id="pay-memo" :label="__('checkout.memo')" :value="$invoice->pay_memo" />
                        <x-alert variant="warning">{{ __('checkout.memo_required') }}</x-alert>
                    @endif

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                        <div class="mb-3 flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ __('checkout.confirmations') }}</span>
                            <span class="text-slate-500">{{ $invoice->currency->confirmations_required }} {{ __('checkout.required') }}</span>
                        </div>
                        <div class="grid grid-cols-6 gap-2">
                            @for($i = 1; $i <= min(6, max(1, $invoice->currency->confirmations_required)); $i++)
                                <div class="h-2 rounded-full {{ $invoice->status === 'paid' ? 'bg-emerald-500' : ($i === 1 ? 'bg-blue-600' : 'bg-slate-200 dark:bg-slate-700') }}"></div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <aside class="space-y-5">
            <x-card :title="__('checkout.live_status')" :subtitle="__('checkout.live_status_subtitle')">
                <div class="space-y-4">
                    @if($invoice->expires_at)
                        <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-4 dark:border-blue-400/20 dark:bg-blue-400/10">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-2 text-sm font-medium text-blue-700 dark:text-blue-100"><x-icon name="clock" class="h-4 w-4" /> {{ __('checkout.time_left') }}</span>
                                <span id="countdown" class="font-mono text-xl font-semibold text-slate-950 dark:text-white">--:--</span>
                            </div>
                        </div>
                    @endif
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/50">
                        <span class="text-sm text-slate-500">{{ __('checkout.network') }}</span>
                        <span class="font-semibold text-slate-950 dark:text-white">{{ $invoice->currency->network }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/50">
                        <span class="text-sm text-slate-500">{{ __('checkout.received') }}</span>
                        <span id="amount-received" class="font-mono font-semibold text-slate-950 dark:text-white">{{ $invoice->amount_received }} {{ $invoice->currency->code }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/50">
                        <span class="text-sm text-slate-500">{{ __('checkout.invoice') }}</span>
                        <span class="font-mono font-semibold text-slate-950 dark:text-white">{{ substr($invoice->uuid, 0, 8) }}</span>
                    </div>
                </div>
            </x-card>

            <x-card :title="__('checkout.payment_details')">
                <dl class="space-y-3 text-sm">
                    @if($invoice->order_id)<div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('checkout.order_id') }}</dt><dd class="text-slate-800 dark:text-slate-200">{{ $invoice->order_id }}</dd></div>@endif
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('checkout.created') }}</dt><dd class="text-slate-800 dark:text-slate-200">{{ $invoice->created_at->format('Y-m-d H:i') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('checkout.required_confirmations') }}</dt><dd class="text-slate-800 dark:text-slate-200">{{ $invoice->currency->confirmations_required }}</dd></div>
                </dl>
            </x-card>
        </aside>
    </div>
</main>

<script>
    @if($invoice->expires_at)
    const expiresAt = new Date('{{ $invoice->expires_at->toIso8601String() }}');
    function updateCountdown() {
        const diff = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
        const m = String(Math.floor(diff / 60)).padStart(2, '0');
        const s = String(diff % 60).padStart(2, '0');
        document.getElementById('countdown').textContent = `${m}:${s}`;
    }
    updateCountdown();
    setInterval(updateCountdown, 1000);
    @endif

    const statusUrl = '{{ route('checkout.status', $invoice->uuid) }}';
    const badge = document.getElementById('status-badge');
    const received = document.getElementById('amount-received');

    async function pollStatus() {
        try {
            const res = await fetch(statusUrl);
            const data = await res.json();
            const label = data.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            badge.textContent = label;
            if (received) received.textContent = `${data.amount_received} {{ $invoice->currency->code }}`;
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
