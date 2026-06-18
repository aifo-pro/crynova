@extends('layouts.app')
@section('title', __('public.sdk.title'))
@section('meta_description', __('public.sdk.meta'))

@section('content')
<section class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:py-20">
    {{-- Header --}}
    <div class="max-w-2xl">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-600">{{ __('public.sdk.badge') }}</p>
        <h1 class="mt-3 text-4xl font-black tracking-[-0.03em] text-slate-950 sm:text-5xl">{{ __('public.sdk.title') }}</h1>
        <p class="mt-5 text-lg leading-8 text-slate-600">{{ __('public.sdk.subtitle') }}</p>
    </div>

    {{-- Download card --}}
    <div class="mt-10 flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6">
        <div class="flex items-center gap-4">
            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-blue-50 text-blue-600">
                <x-icon name="file-text" class="h-7 w-7" />
            </span>
            <div>
                <p class="text-lg font-black text-slate-950">{{ __('public.sdk.file_name') }}</p>
                <p class="text-sm font-semibold text-slate-500">{{ __('public.sdk.file_desc') }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ __('public.sdk.requirements') }}</p>
            </div>
        </div>
        <a href="{{ route('api.sdk.download') }}"
           class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-blue-600 px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            {{ __('public.sdk.download') }}
        </a>
    </div>

    {{-- Getting started steps --}}
    <h2 class="mt-16 text-2xl font-black tracking-[-0.02em] text-slate-950">{{ __('public.sdk.steps_title') }}</h2>
    <div class="mt-8 grid gap-6 md:grid-cols-3">
        @foreach([
            ['1', 'sdk.step1_t', 'sdk.step1_d'],
            ['2', 'sdk.step2_t', 'sdk.step2_d'],
            ['3', 'sdk.step3_t', 'sdk.step3_d'],
        ] as [$num, $tKey, $dKey])
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-blue-600 text-base font-black text-white shadow-lg shadow-blue-100">{{ $num }}</span>
                <h3 class="mt-5 text-lg font-bold text-slate-950">{{ __('public.'.$tKey) }}</h3>
                <p class="mt-2 text-sm leading-7 text-slate-600">{{ __('public.'.$dKey) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Code example --}}
    <h2 class="mt-16 text-2xl font-black tracking-[-0.02em] text-slate-950">{{ __('public.sdk.example_title') }}</h2>
    <div class="mt-6 overflow-hidden rounded-3xl border border-slate-800 bg-slate-950 shadow-xl">
        <div class="flex items-center gap-2 border-b border-white/10 px-5 py-3">
            <span class="h-3 w-3 rounded-full bg-rose-400/80"></span>
            <span class="h-3 w-3 rounded-full bg-amber-400/80"></span>
            <span class="h-3 w-3 rounded-full bg-emerald-400/80"></span>
            <span class="ml-3 font-mono text-xs text-slate-400">checkout.php</span>
        </div>
        <pre class="overflow-x-auto px-5 py-5 text-[13px] leading-6 text-slate-100"><code>use Crynova\CrynovaClient;

$crynova = new CrynovaClient('sk_live_your_api_key');

$invoice = $crynova->createInvoice([
    'currency'    => 'USD',          // fiat or crypto (BTC, USDT_TRC20, …)
    'amount'      => 49.90,
    'order_id'    => 'ORDER-1024',
    'description' => 'Pro plan — 1 month',
    'expires_in'  => 60,             // minutes
], idempotencyKey: 'ORDER-1024');

header('Location: ' . $invoice['checkout_url']);

// ── Verify a webhook ──────────────────────────────
use Crynova\Webhook;

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_CRYNOVA_SIG'] ?? '';
$event     = Webhook::parse($payload, $signature, $webhookSecret);

if ($event['event'] === 'invoice.paid') {
    // fulfil the order
}</code></pre>
    </div>

    {{-- Docs CTA --}}
    <div class="mt-12 flex flex-col gap-4 rounded-3xl border border-slate-200 bg-gradient-to-br from-blue-50 via-white to-cyan-50 p-8 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-lg font-bold text-slate-950">{{ __('public.sdk.docs_title') }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ __('public.sdk.docs_text') }}</p>
        </div>
        <a href="{{ lroute('api.docs') }}" class="inline-flex shrink-0 items-center gap-2 self-start rounded-full bg-slate-950 px-6 py-3 text-sm font-bold text-white transition hover:bg-slate-800 sm:self-auto">
            <x-icon name="book" class="h-4 w-4" /> {{ __('public.sdk.docs_cta') }}
        </a>
    </div>
</section>
@endsection
