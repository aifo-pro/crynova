@extends('layouts.app')
@section('title', __('public.dev.title'))
@section('meta_description', __('public.dev.subtitle'))

@php $apiBase = rtrim(config('app.url'), '/').'/api/v1'; @endphp

@section('content')
<section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">

    {{-- Hero --}}
    <div class="grid items-start gap-10 lg:grid-cols-[0.9fr_1.1fr]">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600">{{ __('public.dev.badge') }}</span>
            <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl">{{ __('public.dev.title') }}</h1>
            <p class="mt-5 text-lg leading-8 text-slate-600">{{ __('public.dev.subtitle') }}</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <x-button href="{{ route('register') }}" icon="key" class="rounded-full">{{ __('public.dev.get_key') }}</x-button>
                <x-button href="{{ route('contact') }}" variant="secondary" icon="message-circle" class="rounded-full">{{ __('public.dev.talk') }}</x-button>
            </div>
            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('public.dev.auth_title') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('public.dev.auth_text') }} <code class="font-mono text-blue-600">{{ $apiBase }}</code></p>
                <div class="mt-3 rounded-xl bg-slate-950 p-3">
                    <code class="block overflow-x-auto text-xs text-slate-200">Authorization: Bearer cryn_live_xxx<br>X-Api-Key: cryn_live_xxx</code>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-800 bg-slate-950 p-5 shadow-xl">
            <div class="mb-2 flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                <span class="ml-2 text-xs text-slate-400">{{ __('public.dev.create_invoice') }}</span>
            </div>
            <pre class="overflow-x-auto text-sm leading-6 text-slate-200"><code>curl -X POST {{ $apiBase }}/invoices \
  -H "Authorization: Bearer cryn_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": "149.00",
    "currency": "USDT_TRC20",
    "order_id": "ORD-1048",
    "description": "Order payment",
    "metadata": { "customer_id": "cus_9281" }
  }'</code></pre>
        </div>
    </div>

    {{-- Capabilities --}}
    <h2 class="mt-16 text-2xl font-semibold text-slate-950">{{ __('public.dev.features_title') }}</h2>
    <div class="mt-5 grid gap-4 md:grid-cols-3">
        @foreach([
            ['credit-card', 'f_create'],
            ['gauge', 'f_status'],
            ['bell', 'f_webhook'],
        ] as [$icon, $k])
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-600"><x-icon :name="$icon" class="h-5 w-5" /></span>
            <h3 class="mt-4 font-semibold text-slate-950">{{ __('public.dev.'.$k) }}</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">{{ __('public.dev.'.$k.'_text') }}</p>
        </div>
        @endforeach
    </div>

    {{-- Endpoints --}}
    <h2 class="mt-16 text-2xl font-semibold text-slate-950">{{ __('public.dev.endpoints') }}</h2>
    <div class="mt-5 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr><th class="px-5 py-3">{{ __('public.dev.method') }}</th><th class="px-5 py-3">{{ __('public.dev.endpoint') }}</th><th class="px-5 py-3">{{ __('public.dev.purpose') }}</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @foreach([
                    ['GET','text-emerald-600','/currencies','ep_currencies'],
                    ['GET','text-emerald-600','/invoices','ep_list'],
                    ['POST','text-blue-600','/invoices','ep_create'],
                    ['GET','text-emerald-600','/invoices/{uuid}','ep_show'],
                    ['GET','text-emerald-600','/invoices/{uuid}/status','ep_status'],
                    ['POST','text-blue-600','/invoices/{uuid}/cancel','ep_cancel'],
                ] as [$m,$c,$ep,$k])
                <tr>
                    <td class="px-5 py-3 font-mono font-semibold {{ $c }}">{{ $m }}</td>
                    <td class="px-5 py-3 font-mono text-slate-800">{{ $ep }}</td>
                    <td class="px-5 py-3 text-slate-500">{{ __('public.dev.'.$k) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Response + Webhook --}}
    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-slate-950">{{ __('public.dev.response') }}</h3>
            <div class="mt-3 rounded-2xl bg-slate-950 p-4">
                <pre class="overflow-x-auto text-xs text-slate-200"><code>{
  "invoice_id": "7a4f...",
  "status": "pending",
  "currency": "USDT_TRC20",
  "amount": "149.00",
  "pay_address": "TR7x...",
  "checkout_url": "{{ rtrim(config('app.url'),'/') }}/pay/7a4f...",
  "expires_at": "2026-06-07T12:30:00+00:00"
}</code></pre>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-slate-950">{{ __('public.dev.webhook_title') }}</h3>
            <div class="mt-3 rounded-2xl bg-slate-950 p-4">
                <pre class="overflow-x-auto text-xs text-slate-200"><code>X-Crynova-Event: invoice.paid
X-Crynova-Sig: sha256=...

{
  "event": "invoice.paid",
  "invoice_id": "7a4f...",
  "status": "paid",
  "amount": "149.00",
  "received": "149.00",
  "currency": "USDT_TRC20"
}</code></pre>
            </div>
        </div>
    </div>

    {{-- Webhook events + signature --}}
    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 font-semibold text-slate-950">{{ __('public.dev.webhook_events') }}</h3>
            <div class="space-y-2 text-sm">
                @foreach([
                    'invoice.created'=>'event_created',
                    'invoice.waiting_confirmations'=>'event_waiting',
                    'invoice.paid'=>'event_paid',
                    'invoice.underpaid'=>'event_underpaid',
                    'invoice.expired'=>'event_expired',
                ] as $ev=>$k)
                <div class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2">
                    <code class="font-mono text-xs text-blue-600">{{ $ev }}</code>
                    <span class="text-slate-500">{{ __('public.dev.'.$k) }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-slate-950">{{ __('public.dev.signature_title') }}</h3>
            <p class="mt-2 text-sm text-slate-500">{{ __('public.dev.signature_text') }}</p>
            <div class="mt-3 rounded-2xl bg-slate-950 p-4">
                <pre class="overflow-x-auto text-xs text-slate-200"><code>$sig = 'sha256=' . hash_hmac(
    'sha256', $rawBody, $secret
);
if (! hash_equals($sig, $header)) {
    abort(401);
}</code></pre>
            </div>
        </div>
    </div>

    {{-- CTA --}}
    <div class="mt-12 rounded-3xl bg-blue-600 p-8 text-center text-white shadow-xl shadow-blue-600/20">
        <h2 class="text-2xl font-semibold">{{ __('public.dev.cta_title') }}</h2>
        <p class="mx-auto mt-2 max-w-xl text-blue-100">{{ __('public.dev.cta_text') }}</p>
        <div class="mt-6 flex justify-center gap-3">
            <a href="{{ route('register') }}" class="rounded-full bg-white px-6 py-3 text-sm font-semibold text-blue-600 hover:bg-blue-50">{{ __('public.dev.get_key') }}</a>
            <a href="{{ route('contact') }}" class="rounded-full border border-white/40 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10">{{ __('public.dev.talk') }}</a>
        </div>
    </div>
</section>
@endsection
