@extends('layouts.app')
@section('title', 'API ' . __('public.apidocs.title'))
@section('meta_description', __('public.apidocs.meta'))

@php
    $appUrl  = rtrim(config('app.url'), '/');
    $apiBase = $appUrl . '/api/v1';
    $fiat    = (array) config('crynova.fiat_currencies', []);

    $nav = [
        ['intro', __('public.apidocs.nav.intro'), 'book'],
        ['base', __('public.apidocs.nav.base'), 'globe'],
        ['auth', __('public.apidocs.nav.auth'), 'lock'],
        ['config', __('public.apidocs.nav.config'), 'settings'],
        ['currencies', 'GET /currencies', 'coins'],
        ['create', 'POST /invoices', 'wallet'],
        ['fiat', __('public.apidocs.nav.fiat'), 'banknote'],
        ['retrieve', 'GET /invoices/{id}', 'file-text'],
        ['status', 'GET /invoices/{id}/status', 'clock'],
        ['list', 'GET /invoices', 'layers'],
        ['cancel', 'POST /invoices/{id}/cancel', 'alert-triangle'],
        ['webhooks', __('public.apidocs.nav.webhooks'), 'bell'],
        ['errors', __('public.apidocs.nav.errors'), 'shield'],
        ['limits', __('public.apidocs.nav.limits'), 'key'],
    ];

    $events = [
        ['invoice.created', __('public.apidocs.ev.created')],
        ['invoice.waiting_confirmations', __('public.apidocs.ev.waiting')],
        ['invoice.paid', __('public.apidocs.ev.paid')],
        ['invoice.underpaid', __('public.apidocs.ev.underpaid')],
        ['invoice.overpaid', __('public.apidocs.ev.overpaid')],
        ['invoice.expired', __('public.apidocs.ev.expired')],
        ['invoice.refunded', __('public.apidocs.ev.refunded')],
    ];
@endphp

@push('jsonld')
<script type="application/ld+json">{!! json_encode([
    '@context'=>'https://schema.org','@type'=>'TechArticle',
    'headline'=>'Crynova API','description'=>__('public.apidocs.meta'),
    'author'=>['@type'=>'Organization','name'=>'Crynova'],
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
@php
    // Small helpers for reuse inside this view.
    $badge = fn($m) => match($m) {
        'GET' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
        'POST' => 'bg-blue-50 text-blue-600 ring-blue-100',
        'DELETE' => 'bg-rose-50 text-rose-600 ring-rose-100',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
@endphp

{{-- Hero --}}
<section class="border-b border-slate-100 bg-gradient-to-b from-blue-50/60 via-white to-white">
    <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
        <span class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">
            <x-icon name="layers" class="h-3.5 w-3.5" /> Crynova API v1
        </span>
        <h1 class="mt-5 text-4xl font-black tracking-[-0.03em] text-slate-950 sm:text-5xl">{{ __('public.apidocs.title') }}</h1>
        <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-600">{{ __('public.apidocs.subtitle') }}</p>

        <div class="mt-7 flex flex-wrap items-center gap-3">
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700"><x-icon name="key" class="h-4 w-4" /> {{ __('public.apidocs.get_key') }}</a>
            <a href="#create" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-6 py-3 text-sm font-bold text-slate-800 hover:border-blue-200"><x-icon name="arrow-right" class="h-4 w-4" /> {{ __('public.apidocs.quickstart') }}</a>
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2.5 font-mono text-xs text-slate-600"><x-icon name="globe" class="h-4 w-4 text-blue-500" /> {{ $apiBase }}</span>
        </div>

        <div class="mt-8 grid gap-3 sm:grid-cols-3">
            @foreach([
                ['shield', __('public.apidocs.feat1_t'), __('public.apidocs.feat1_d')],
                ['banknote', __('public.apidocs.feat2_t'), __('public.apidocs.feat2_d')],
                ['bell', __('public.apidocs.feat3_t'), __('public.apidocs.feat3_d')],
            ] as [$ic,$t,$d])
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-50 text-blue-600"><x-icon :name="$ic" class="h-5 w-5" /></span>
                    <p class="mt-3 font-bold text-slate-950">{{ $t }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<div class="mx-auto max-w-6xl gap-10 px-4 py-12 sm:px-6 lg:grid lg:grid-cols-[15rem_1fr] lg:px-8">
    {{-- Sidebar --}}
    <aside class="hidden lg:block">
        <nav class="sticky top-28 space-y-1">
            @foreach($nav as [$id,$label,$ic])
                <a href="#{{ $id }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                    <x-icon :name="$ic" class="h-4 w-4 shrink-0 text-slate-400" /> <span class="truncate">{{ $label }}</span>
                </a>
            @endforeach
        </nav>
    </aside>

    {{-- Content --}}
    <div class="min-w-0 space-y-12" x-data="{ copy(id){ const el=document.getElementById(id); navigator.clipboard.writeText(el.innerText); } }">

        {{-- Intro --}}
        <section id="intro" class="scroll-mt-28">
            <h2 class="text-2xl font-black text-slate-950">{{ __('public.apidocs.nav.intro') }}</h2>
            <p class="mt-3 leading-7 text-slate-600">{{ __('public.apidocs.intro') }}</p>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @foreach([
                    ['coins', __('public.apidocs.intro1')],
                    ['banknote', __('public.apidocs.intro2')],
                    ['bell', __('public.apidocs.intro3')],
                    ['shield', __('public.apidocs.intro4')],
                ] as [$ic,$txt])
                    <div class="flex gap-3 rounded-2xl border border-slate-200 bg-white p-4">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-600"><x-icon :name="$ic" class="h-4 w-4" /></span>
                        <p class="text-sm leading-6 text-slate-600">{{ $txt }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Base URL --}}
        <section id="base" class="scroll-mt-28">
            <h2 class="text-2xl font-black text-slate-950">{{ __('public.apidocs.nav.base') }}</h2>
            <p class="mt-3 leading-7 text-slate-600">{{ __('public.apidocs.base_text') }}</p>
            <x-api-code id="base-code" lang="HTTP">{{ $apiBase }}</x-api-code>
            <ul class="mt-4 space-y-2 text-sm text-slate-600">
                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 text-emerald-500" /> {{ __('public.apidocs.base1') }}</li>
                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 text-emerald-500" /> {{ __('public.apidocs.base2') }}</li>
            </ul>
        </section>

        {{-- Auth --}}
        <section id="auth" class="scroll-mt-28">
            <h2 class="text-2xl font-black text-slate-950">{{ __('public.apidocs.nav.auth') }}</h2>
            <p class="mt-3 leading-7 text-slate-600">{{ __('public.apidocs.auth_text') }}</p>
            <x-api-code id="auth-code" lang="HTTP">Authorization: Bearer cryn_xxxxxxxxxxxxxxxx
# {{ __('public.apidocs.auth_alt') }}
X-Api-Key: cryn_xxxxxxxxxxxxxxxx</x-api-code>
            <div class="mt-4 flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <x-icon name="alert-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                <p class="text-sm leading-6 text-amber-800">{{ __('public.apidocs.auth_warn') }}</p>
            </div>
        </section>

        {{-- Config --}}
        <section id="config" class="scroll-mt-28">
            <h2 class="text-2xl font-black text-slate-950">{{ __('public.apidocs.nav.config') }}</h2>
            <ol class="mt-4 space-y-3">
                @foreach([
                    __('public.apidocs.cfg1'),
                    __('public.apidocs.cfg2'),
                    __('public.apidocs.cfg3'),
                    __('public.apidocs.cfg4'),
                ] as $i => $step)
                    <li class="flex gap-3 rounded-2xl border border-slate-200 bg-white p-4">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-blue-600 text-sm font-black text-white">{{ $i+1 }}</span>
                        <p class="text-sm leading-6 text-slate-600">{!! $step !!}</p>
                    </li>
                @endforeach
            </ol>
            <h3 class="mt-6 font-bold text-slate-950">{{ __('public.apidocs.perms_title') }}</h3>
            <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Permission</th><th class="px-4 py-3">{{ __('public.apidocs.access') }}</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <tr><td class="px-4 py-3 font-mono text-blue-600">currencies.read</td><td class="px-4 py-3">{{ __('public.apidocs.perm_cur') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono text-blue-600">invoices.create</td><td class="px-4 py-3">{{ __('public.apidocs.perm_create') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono text-blue-600">invoices.read</td><td class="px-4 py-3">{{ __('public.apidocs.perm_read') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono text-blue-600">invoices.cancel</td><td class="px-4 py-3">{{ __('public.apidocs.perm_cancel') }}</td></tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-2 text-xs text-slate-400">{{ __('public.apidocs.perms_note') }}</p>
        </section>

        {{-- Currencies --}}
        <section id="currencies" class="scroll-mt-28">
            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-lg px-2.5 py-1 text-xs font-black ring-1 {{ $badge('GET') }}">GET</span>
                <code class="font-mono text-sm font-bold text-slate-950">/api/v1/currencies</code>
            </div>
            <p class="mt-3 leading-7 text-slate-600">{{ __('public.apidocs.cur_text') }}</p>
            <x-api-code id="cur-req" lang="cURL">curl {{ $apiBase }}/currencies \
  -H "Authorization: Bearer cryn_xxx"</x-api-code>
            <p class="mt-4 text-sm font-bold text-slate-700">{{ __('public.apidocs.response') }}</p>
            <x-api-code id="cur-res" lang="JSON">{
  "data": [
    {
      "code": "USDT_TRC20",
      "name": "Tether USD (TRC-20)",
      "network": "tron",
      "contract_address": "TR7NHq...",
      "decimals": 6,
      "confirmations_required": 20,
      "min_amount": "1",
      "max_amount": null,
      "estimated_fee": "1.4",
      "supports_memo": false
    }
  ],
  "fiat": [{{ '"' . implode('","', array_slice($fiat, 0, 6)) . '"' }}, ...]
}</x-api-code>
        </section>

        {{-- Create --}}
        <section id="create" class="scroll-mt-28">
            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-lg px-2.5 py-1 text-xs font-black ring-1 {{ $badge('POST') }}">POST</span>
                <code class="font-mono text-sm font-bold text-slate-950">/api/v1/invoices</code>
            </div>
            <p class="mt-3 leading-7 text-slate-600">{{ __('public.apidocs.create_text') }}</p>

            <h3 class="mt-5 font-bold text-slate-950">{{ __('public.apidocs.params') }}</h3>
            <div class="mt-3 overflow-x-auto rounded-2xl border border-slate-200">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">{{ __('public.apidocs.field') }}</th><th class="px-4 py-3">{{ __('public.apidocs.type') }}</th><th class="px-4 py-3">{{ __('public.apidocs.req') }}</th><th class="px-4 py-3">{{ __('public.apidocs.desc') }}</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <tr><td class="px-4 py-3 font-mono">currency</td><td class="px-4 py-3">string</td><td class="px-4 py-3"><span class="text-rose-500">{{ __('public.apidocs.yes') }}</span></td><td class="px-4 py-3">{{ __('public.apidocs.p_currency') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono">amount</td><td class="px-4 py-3">string|number</td><td class="px-4 py-3"><span class="text-rose-500">{{ __('public.apidocs.yes') }}</span></td><td class="px-4 py-3">{{ __('public.apidocs.p_amount') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono">order_id</td><td class="px-4 py-3">string</td><td class="px-4 py-3 text-slate-400">{{ __('public.apidocs.no') }}</td><td class="px-4 py-3">{{ __('public.apidocs.p_order') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono">description</td><td class="px-4 py-3">string</td><td class="px-4 py-3 text-slate-400">{{ __('public.apidocs.no') }}</td><td class="px-4 py-3">{{ __('public.apidocs.p_desc') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono">expires_in</td><td class="px-4 py-3">integer</td><td class="px-4 py-3 text-slate-400">{{ __('public.apidocs.no') }}</td><td class="px-4 py-3">{{ __('public.apidocs.p_expires') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono">metadata</td><td class="px-4 py-3">object</td><td class="px-4 py-3 text-slate-400">{{ __('public.apidocs.no') }}</td><td class="px-4 py-3">{{ __('public.apidocs.p_meta') }}</td></tr>
                    </tbody>
                </table>
            </div>

            <p class="mt-5 text-sm font-bold text-slate-700">{{ __('public.apidocs.example_crypto') }}</p>
            <x-api-code id="cr-req" lang="cURL">curl -X POST {{ $apiBase }}/invoices \
  -H "Authorization: Bearer cryn_xxx" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: order-1048" \
  -d '{
    "currency": "USDT_TRC20",
    "amount": "25.00",
    "order_id": "ORD-1048",
    "description": "Order #1048",
    "expires_in": 30,
    "metadata": { "customer_id": "42" }
  }'</x-api-code>
            <p class="mt-4 text-sm font-bold text-slate-700">{{ __('public.apidocs.response') }}</p>
            <x-api-code id="cr-res" lang="JSON">{
  "invoice_id": "9ae4cd13-...",
  "order_id": "ORD-1048",
  "status": "pending",
  "price_amount": "25",
  "price_currency": "USDT_TRC20",
  "pay_currency": "USDT_TRC20",
  "currency": "USDT_TRC20",
  "amount": "25.000000000000000000",
  "amount_received": "0",
  "pay_address": "TR7NHq...",
  "pay_memo": null,
  "expires_at": "2026-06-11T12:30:00+00:00",
  "checkout_url": "{{ $appUrl }}/pay/9ae4cd13-...",
  "transactions": []
}</x-api-code>
        </section>

        {{-- Fiat --}}
        <section id="fiat" class="scroll-mt-28">
            <div class="rounded-3xl border border-blue-100 bg-gradient-to-br from-blue-50 via-white to-cyan-50 p-6">
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-blue-600 text-white"><x-icon name="banknote" class="h-6 w-6" /></span>
                    <h2 class="text-2xl font-black text-slate-950">{{ __('public.apidocs.nav.fiat') }}</h2>
                </div>
                <p class="mt-4 leading-7 text-slate-600">{{ __('public.apidocs.fiat_text') }}</p>
                <p class="mt-3 text-sm font-semibold text-slate-700">{{ __('public.apidocs.fiat_supported') }}</p>
                <p class="mt-1 font-mono text-xs leading-5 text-slate-500">{{ implode(', ', $fiat) }}</p>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div>
                        <p class="mb-2 text-sm font-bold text-slate-700">{{ __('public.apidocs.request') }}</p>
                        <x-api-code id="fiat-req" lang="JSON">{
  "currency": "UAH",
  "amount": "499.00",
  "order_id": "ORD-1001"
}</x-api-code>
                    </div>
                    <div>
                        <p class="mb-2 text-sm font-bold text-slate-700">{{ __('public.apidocs.response') }}</p>
                        <x-api-code id="fiat-res" lang="JSON">{
  "status": "pending",
  "price_amount": "499",
  "price_currency": "UAH",
  "pay_currency": null,
  "amount": null,
  "pay_address": null,
  "checkout_url": "{{ $appUrl }}/pay/..."
}</x-api-code>
                    </div>
                </div>
                <ol class="mt-5 space-y-2 text-sm text-slate-700">
                    <li class="flex gap-2"><span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-blue-600 text-[11px] font-bold text-white">1</span> {{ __('public.apidocs.fiat_s1') }}</li>
                    <li class="flex gap-2"><span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-blue-600 text-[11px] font-bold text-white">2</span> {{ __('public.apidocs.fiat_s2') }}</li>
                    <li class="flex gap-2"><span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-blue-600 text-[11px] font-bold text-white">3</span> {{ __('public.apidocs.fiat_s3') }}</li>
                </ol>
            </div>
        </section>

        {{-- Retrieve --}}
        <section id="retrieve" class="scroll-mt-28">
            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-lg px-2.5 py-1 text-xs font-black ring-1 {{ $badge('GET') }}">GET</span>
                <code class="font-mono text-sm font-bold text-slate-950">/api/v1/invoices/{invoice_id}</code>
            </div>
            <p class="mt-3 leading-7 text-slate-600">{{ __('public.apidocs.retrieve_text') }}</p>
            <x-api-code id="get-req" lang="cURL">curl {{ $apiBase }}/invoices/9ae4cd13-... \
  -H "Authorization: Bearer cryn_xxx"</x-api-code>
        </section>

        {{-- Status --}}
        <section id="status" class="scroll-mt-28">
            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-lg px-2.5 py-1 text-xs font-black ring-1 {{ $badge('GET') }}">GET</span>
                <code class="font-mono text-sm font-bold text-slate-950">/api/v1/invoices/{invoice_id}/status</code>
            </div>
            <p class="mt-3 leading-7 text-slate-600">{{ __('public.apidocs.status_text') }}</p>
            <x-api-code id="st-res" lang="JSON">{
  "invoice_id": "9ae4cd13-...",
  "status": "paid",
  "is_final": true,
  "amount": "25.0",
  "amount_received": "25.0",
  "currency": "USDT_TRC20",
  "confirmations": 20,
  "confirmations_required": 20,
  "paid_at": "2026-06-11T12:31:00+00:00"
}</x-api-code>
            <p class="mt-3 text-sm text-slate-500">{{ __('public.apidocs.statuses') }}: <code class="text-blue-600">pending</code>, <code class="text-blue-600">waiting_confirmations</code>, <code class="text-blue-600">paid</code>, <code class="text-blue-600">underpaid</code>, <code class="text-blue-600">overpaid</code>, <code class="text-blue-600">expired</code>, <code class="text-blue-600">refunded</code>.</p>
        </section>

        {{-- List --}}
        <section id="list" class="scroll-mt-28">
            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-lg px-2.5 py-1 text-xs font-black ring-1 {{ $badge('GET') }}">GET</span>
                <code class="font-mono text-sm font-bold text-slate-950">/api/v1/invoices</code>
            </div>
            <p class="mt-3 leading-7 text-slate-600">{{ __('public.apidocs.list_text') }}</p>
            <x-api-code id="ls-req" lang="cURL">curl "{{ $apiBase }}/invoices?status=paid&per_page=50" \
  -H "Authorization: Bearer cryn_xxx"</x-api-code>
            <p class="mt-2 text-sm text-slate-500">{{ __('public.apidocs.list_filters') }}: <code>status</code>, <code>order_id</code>, <code>currency</code>, <code>per_page</code> (1–100).</p>
        </section>

        {{-- Cancel --}}
        <section id="cancel" class="scroll-mt-28">
            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-lg px-2.5 py-1 text-xs font-black ring-1 {{ $badge('POST') }}">POST</span>
                <code class="font-mono text-sm font-bold text-slate-950">/api/v1/invoices/{invoice_id}/cancel</code>
            </div>
            <p class="mt-3 leading-7 text-slate-600">{{ __('public.apidocs.cancel_text') }}</p>
            <x-api-code id="cancel-req" lang="cURL">curl -X POST {{ $apiBase }}/invoices/9ae4cd13-.../cancel \
  -H "Authorization: Bearer cryn_xxx"</x-api-code>
        </section>

        {{-- Webhooks --}}
        <section id="webhooks" class="scroll-mt-28">
            <h2 class="text-2xl font-black text-slate-950">{{ __('public.apidocs.nav.webhooks') }}</h2>
            <p class="mt-3 leading-7 text-slate-600">{{ __('public.apidocs.wh_text') }}</p>

            <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">{{ __('public.apidocs.event') }}</th><th class="px-4 py-3">{{ __('public.apidocs.when') }}</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($events as [$ev,$when])
                            <tr><td class="px-4 py-3 font-mono text-blue-600">{{ $ev }}</td><td class="px-4 py-3">{{ $when }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="mt-5 text-sm font-bold text-slate-700">{{ __('public.apidocs.wh_payload') }}</p>
            <x-api-code id="wh-payload" lang="HTTP">POST {your_webhook_url}
X-Crynova-Event: invoice.paid
X-Crynova-Sig: sha256=hmac(secret, body)
X-Crynova-Delivery: 123

{
  "event": "invoice.paid",
  "invoice_id": "9ae4cd13-...",
  "order_id": "ORD-1048",
  "status": "paid",
  "price_amount": "499",
  "price_currency": "UAH",
  "pay_currency": "USDT_TRC20",
  "amount": "12.5",
  "received": "12.5",
  "metadata": { "customer_id": "42" }
}</x-api-code>

            <p class="mt-5 text-sm font-bold text-slate-700">{{ __('public.apidocs.wh_verify') }}</p>
            <x-api-code id="wh-verify" lang="PHP">$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_CRYNOVA_SIG'] ?? '';
$expected = 'sha256=' . hash_hmac('sha256', $payload, $YOUR_WEBHOOK_SECRET);

if (! hash_equals($expected, $signature)) {
    http_response_code(403);
    exit('Invalid signature');
}
// trusted — process the event
http_response_code(200);</x-api-code>
            <div class="mt-4 flex gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <x-icon name="clock" class="mt-0.5 h-5 w-5 shrink-0 text-slate-500" />
                <p class="text-sm leading-6 text-slate-600">{{ __('public.apidocs.wh_retry') }}</p>
            </div>
        </section>

        {{-- Errors --}}
        <section id="errors" class="scroll-mt-28">
            <h2 class="text-2xl font-black text-slate-950">{{ __('public.apidocs.nav.errors') }}</h2>
            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">HTTP</th><th class="px-4 py-3">{{ __('public.apidocs.meaning') }}</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <tr><td class="px-4 py-3 font-mono text-emerald-600">200</td><td class="px-4 py-3">{{ __('public.apidocs.e200') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono text-rose-500">401</td><td class="px-4 py-3">{{ __('public.apidocs.e401') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono text-rose-500">403</td><td class="px-4 py-3">{{ __('public.apidocs.e403') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono text-rose-500">404</td><td class="px-4 py-3">{{ __('public.apidocs.e404') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono text-rose-500">422</td><td class="px-4 py-3">{{ __('public.apidocs.e422') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono text-rose-500">429</td><td class="px-4 py-3">{{ __('public.apidocs.e429') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Limits --}}
        <section id="limits" class="scroll-mt-28">
            <h2 class="text-2xl font-black text-slate-950">{{ __('public.apidocs.nav.limits') }}</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach([
                    ['clock', __('public.apidocs.l_rate')],
                    ['key', __('public.apidocs.l_idem')],
                    ['lock', __('public.apidocs.l_ip')],
                    ['shield', __('public.apidocs.l_perms')],
                ] as [$ic,$txt])
                    <div class="flex gap-3 rounded-2xl border border-slate-200 bg-white p-4">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-blue-50 text-blue-600"><x-icon :name="$ic" class="h-4 w-4" /></span>
                        <p class="text-sm leading-6 text-slate-600">{!! $txt !!}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- CTA --}}
        <section class="rounded-3xl border border-slate-200 bg-gradient-to-br from-blue-600 to-indigo-600 p-8 text-center text-white">
            <h2 class="text-2xl font-black">{{ __('public.apidocs.cta_title') }}</h2>
            <p class="mx-auto mt-2 max-w-xl text-blue-50">{{ __('public.apidocs.cta_text') }}</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-bold text-blue-600 hover:bg-blue-50"><x-icon name="key" class="h-4 w-4" /> {{ __('public.apidocs.get_key') }}</a>
                <a href="{{ lroute('contact') }}" class="inline-flex items-center gap-2 rounded-full border border-white/40 px-6 py-3 text-sm font-bold text-white hover:bg-white/10"><x-icon name="message-circle" class="h-4 w-4" /> {{ __('public.apidocs.support') }}</a>
            </div>
        </section>
    </div>
</div>
@endsection
