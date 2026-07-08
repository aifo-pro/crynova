<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('checkout.pay_with', ['currency' => $invoice->currency->name]) }} - Crynova</title>
    <link rel="icon" href="{{ asset('assets/crynova/favicon/favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('assets/crynova/favicon/apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $formatCrypto = function ($value) {
        $value = rtrim(rtrim((string) $value, '0'), '.');
        return $value === '' ? '0' : $value;
    };
    $formatCompact = function ($value) {
        $v = rtrim(rtrim(bcadd((string) $value, '0', 8), '0'), '.');
        return $v === '' || $v === '-0' ? '0' : $v;
    };

    $baseAmount = $formatCompact($invoice->amount);
    $transferFee = $formatCompact($invoice->transferFee());
    $hasTransferFee = bccomp((string) $invoice->transferFee(), '0', 18) > 0;
    $payable = $invoice->payableAmount();
    $amount = $formatCrypto($payable);            // exact, authoritative
    $amountCompact = $formatCompact($payable);    // compact display
    $receivedAmount = $formatCompact($invoice->amount_received);
    $currencyCode = $invoice->currency->code;
    $networkLabel = match (true) {
        str_contains($currencyCode, 'TRC20') => 'TRC-20',
        str_contains($currencyCode, 'ERC20') => 'ERC-20',
        str_contains($currencyCode, 'BEP20') => 'BEP-20',
        default => strtoupper((string) $invoice->currency->network),
    };
    $requiredConfirmations = max(1, (int) $invoice->currency->confirmations_required);
    $currentConfirmations = $invoice->status === 'paid'
        ? $requiredConfirmations
        : min($requiredConfirmations, max(0, (int) $invoice->transactions->max('confirmations')));
    $expiresLeft = $invoice->expires_at ? max(0, (int) now()->diffInSeconds($invoice->expires_at, false)) : null;

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
@endphp
<body class="min-h-screen bg-[#f7f8fb] text-slate-950 antialiased">
<script src="https://unpkg.com/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js"></script>
<main class="mx-auto flex min-h-screen max-w-lg flex-col px-4 py-8" x-data="{ modal: false }">

    {{-- Header --}}
    <header class="mb-5 flex items-center justify-between gap-3">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5">
            <x-logo variant="mark" class="h-9 w-9 rounded-xl shadow-md shadow-blue-600/20" />
            <span class="text-base font-black tracking-tight text-slate-900">Crynova</span>
        </a>
        <div class="flex items-center gap-3">
            @if($expiresLeft !== null)
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-600">
                    <x-icon name="clock" class="h-3.5 w-3.5" />
                    <span id="countdown">--:--</span>
                </span>
            @endif
            <x-language-switcher compact drop="down" />
        </div>
    </header>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60">
        {{-- Invoice + merchant --}}
        <div class="flex items-center justify-between text-sm">
            <span class="font-bold text-slate-900">{{ $invoice->order_id ?: ('#'.substr($invoice->uuid,0,8)) }}</span>
            <span class="inline-flex items-center gap-1.5 font-semibold text-slate-500"><x-icon name="wallet" class="h-4 w-4" /> {{ $invoice->merchant->name }}</span>
        </div>

        {{-- Amount + ? + copy --}}
        <div class="mt-4 flex items-center justify-between gap-3">
            <span class="text-sm font-semibold text-slate-500">{{ __('checkout.amount_due') }}</span>
            <span class="flex items-center gap-2">
                <span id="pay-amount-text" class="font-mono text-base font-black text-slate-950">{{ $amountCompact }} <span class="text-blue-600">{{ $currencyCode }}</span></span>
                <x-copy-button target="pay-amount-hidden" class="h-7 w-7" />
                <span id="pay-amount-hidden" class="hidden">{{ $amount }}</span>
            </span>
        </div>

        {{-- Address --}}
        <div class="mt-3 flex items-center justify-between gap-3">
            <span class="shrink-0 text-sm font-semibold text-slate-500">{{ __('checkout.wallet_address') }}</span>
            <span class="flex min-w-0 items-center gap-2">
                <code id="pay-address-text" class="truncate font-mono text-sm font-semibold text-blue-600">{{ $invoice->pay_address }}</code>
                <x-copy-button target="pay-address-text" class="h-7 w-7 shrink-0" />
            </span>
        </div>
        @if($invoice->pay_memo)
            <div class="mt-3 flex items-center justify-between gap-3">
                <span class="shrink-0 text-sm font-semibold text-slate-500">{{ __('checkout.memo') }}</span>
                <span class="flex min-w-0 items-center gap-2">
                    <code id="pay-memo-text" class="truncate font-mono text-sm font-semibold text-amber-600">{{ $invoice->pay_memo }}</code>
                    <x-copy-button target="pay-memo-text" class="h-7 w-7 shrink-0" />
                </span>
            </div>
        @endif

        {{-- Status bar --}}
        <div class="mt-5 flex items-center gap-3 rounded-2xl border-l-4 border-blue-500 bg-slate-50 px-4 py-3">
            <svg id="status-spinner" class="h-5 w-5 animate-spin text-blue-500" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0a12 12 0 0 0-12 12h4Z"/></svg>
            <span id="status-badge" class="text-sm font-bold text-slate-700">{{ $statusLabels[$invoice->status] ?? $invoice->status }}</span>
        </div>

        {{-- QR + selection --}}
        @php
            $coinBase = strtolower(explode('_', $currencyCode)[0]);
            $coinIcon = in_array($coinBase, ['btc','eth','usdt','trx','ltc','doge'], true) ? asset('assets/crynova/crypto-icons/'.$coinBase.'.svg') : null;
        @endphp
        <div class="mt-4 flex flex-col items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                 x-data="{ q: null,
                    init() {
                        this.q = new QRCodeStyling({
                            width: 264, height: 264, type: 'svg',
                            data: @js($qrData),
                            @if($coinIcon) image: '{{ $coinIcon }}', @endif
                            margin: 6,
                            qrOptions: { errorCorrectionLevel: 'H' },
                            dotsOptions: { color: '#1e293b', type: 'rounded' },
                            cornersSquareOptions: { type: 'extra-rounded', color: '#2563eb' },
                            cornersDotOptions: { color: '#2563eb' },
                            backgroundOptions: { color: '#ffffff' },
                            imageOptions: { crossOrigin: 'anonymous', margin: 6, imageSize: 0.3 },
                        });
                        this.$nextTick(() => { this.$refs.qr.innerHTML = ''; this.q.append(this.$refs.qr); });
                    } }">
                <div x-ref="qr" class="flex justify-center [&>svg]:block"></div>
            </div>
            <div class="flex w-full items-center justify-center gap-3">
                <div class="flex items-center gap-2 rounded-xl bg-white px-3 py-2 shadow-sm ring-1 ring-slate-200">
                    <x-coin-icon :code="$currencyCode" class="h-6 w-6" />
                    <div class="leading-tight">
                        <p class="text-[10px] uppercase tracking-wide text-slate-500">{{ __('checkout.currency') }}</p>
                        <p class="text-sm font-bold text-slate-900">{{ explode('_', $currencyCode)[0] }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 rounded-xl bg-white px-3 py-2 shadow-sm ring-1 ring-slate-200">
                    <div class="leading-tight">
                        <p class="text-[10px] uppercase tracking-wide text-slate-500">{{ __('checkout.network') }}</p>
                        <p class="text-sm font-bold text-slate-900">{{ $networkLabel }}</p>
                    </div>
                </div>
            </div>
            <p class="text-center text-xs leading-5 text-slate-500">{{ __('checkout.scan_wallet') }}</p>
        </div>

        {{-- Confirmations --}}
        <div class="mt-4">
            <div class="mb-1.5 flex items-center justify-between text-sm">
                <span class="font-semibold text-slate-500">{{ __('checkout.confirmations') }}</span>
                <span class="font-mono font-bold text-slate-900"><span id="confirmations-current">{{ $currentConfirmations }}</span> / {{ $requiredConfirmations }}</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                <div id="confirmations-progress" class="h-full rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 transition-all duration-500" style="width: {{ min(100, round($currentConfirmations / $requiredConfirmations * 100)) }}%"></div>
            </div>
        </div>

        {{-- Refresh --}}
        <button type="button" onclick="location.reload()" class="mt-5 w-full rounded-full border border-blue-200 py-3 text-sm font-bold text-blue-600 transition hover:bg-blue-50">
            {{ __('checkout.select.check_tx') }}
        </button>

        <div class="mt-4 flex items-center justify-between text-xs text-slate-500"><span>{{ __('checkout.select.powered') }} <span class="font-bold text-slate-500">Crynova</span></span><a href="{{ url('/tos') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-semibold text-slate-500 hover:text-blue-600"><x-icon name="book" class="h-3.5 w-3.5" /> {{ __('checkout.select.terms') }}</a></div>
    </div>

    {{-- Fee breakdown modal --}}
    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-slate-900/40" @click="modal=false"></div>
        <div x-show="modal" x-transition class="relative w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-950">{{ __('checkout.select.m_title') }}</h2>
                <button type="button" @click="modal=false" class="grid h-8 w-8 place-items-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200"><x-icon name="x" class="h-4 w-4" /></button>
            </div>
            <div class="mt-5 space-y-3 text-sm">
                <div class="flex items-center justify-between"><span class="text-slate-500">{{ __('checkout.select.m_currency') }}</span><span class="font-bold text-slate-900">{{ $invoice->currency->name }}</span></div>
                <div class="flex items-center justify-between"><span class="text-slate-500">{{ __('checkout.select.m_network') }}</span><span class="font-bold text-slate-900">{{ $networkLabel }}</span></div>
                <div class="flex items-center justify-between"><span class="text-slate-500">{{ __('checkout.invoice_amount') }}</span><span class="font-mono font-bold text-slate-900">{{ $baseAmount }} {{ $currencyCode }}</span></div>
                @if($hasTransferFee)
                <div class="flex items-center justify-between"><span class="text-slate-500">{{ __('checkout.transfer_fee') }}</span><span class="font-mono font-bold text-amber-600">+ {{ $transferFee }} {{ $currencyCode }}</span></div>
                @endif
                <div class="flex items-center justify-between"><span class="text-slate-500">{{ __('checkout.received') }}</span><span class="font-mono font-bold text-slate-900">{{ $receivedAmount }} {{ $currencyCode }}</span></div>
                <div class="flex items-center justify-between border-t border-slate-100 pt-3"><span class="font-bold text-slate-700">{{ __('checkout.total_due') }}</span><span class="font-mono text-base font-black text-blue-700">{{ $amountCompact }} {{ $currencyCode }}</span></div>
            </div>
            @if($hasTransferFee)
            <p class="mt-5 flex items-start gap-2 rounded-xl bg-slate-50 p-3 text-xs leading-5 text-slate-500">
                <x-icon name="alert-triangle" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-500" />
                {{ __('checkout.transfer_fee_note') }}
            </p>
            @endif
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
        const el = document.getElementById('countdown');
        if (el) el.textContent = `${m}:${s}`;
        if (diff <= 0 && !expiredHandled) { expiredHandled = true; setTimeout(() => location.reload(), 1200); }
    }
    updateCountdown();
    setInterval(updateCountdown, 1000);
    @endif

    const statusUrl = '{{ route('checkout.status', $invoice->uuid) }}';
    const statusLabels = @json($statusLabels);
    const requiredConfirmations = {{ $requiredConfirmations }};
    const badge = document.getElementById('status-badge');
    const confirmationsCurrent = document.getElementById('confirmations-current');
    const confirmationsProgress = document.getElementById('confirmations-progress');

    async function pollStatus() {
        try {
            const res = await fetch(statusUrl);
            const data = await res.json();
            if (badge) badge.textContent = statusLabels[data.status] || data.status;
            if (confirmationsCurrent && confirmationsProgress && data.confirmations !== undefined) {
                const required = Number(data.confirmations_required || requiredConfirmations || 1);
                const current = Math.min(required, Math.max(0, Number(data.confirmations || 0)));
                confirmationsCurrent.textContent = current;
                confirmationsProgress.style.width = `${Math.min(100, Math.round((current / Math.max(required, 1)) * 100))}%`;
            }
            if (data.is_final) { location.reload(); return; }
        } catch (e) {}
        setTimeout(pollStatus, 5000);
    }
    setTimeout(pollStatus, 5000);
</script>
</body>
</html>
