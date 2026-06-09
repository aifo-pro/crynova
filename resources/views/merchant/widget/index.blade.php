@extends('layouts.app')
@section('title', 'Checkout Widget')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-950 dark:text-white">Checkout widget</h1>
        <p class="mt-1 text-slate-500">Embed a crypto payment button or inline checkout on any website in minutes.</p>
    </div>

    @if(! $apiKey)
    <x-alert variant="warning" title="No active API key">
        You need an active API key to use the widget.
        <a href="{{ route('merchant.api-keys.index') }}" class="font-semibold underline">Create one →</a>
    </x-alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_0.55fr]">
        {{-- ── Integration options ────────────────────────────────────── --}}
        <div class="space-y-6">
            {{-- Option 1: JS button --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                <div class="mb-4 flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-400/10 dark:text-blue-400">
                        <x-icon name="link" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="font-semibold text-slate-950 dark:text-white">Option 1 — Hosted checkout button</p>
                        <p class="text-sm text-slate-500">Redirect customers to a hosted Crynova checkout page. Zero iframe needed.</p>
                    </div>
                </div>
                <div class="rounded-2xl bg-slate-950 p-4">
                    <pre id="snippet-redirect" class="overflow-x-auto text-xs text-slate-200"><code>&lt;!-- Crynova Checkout Button --&gt;
&lt;a href="{{ rtrim(config('app.url'), '/') }}/pay/{INVOICE_UUID}"
   class="crynova-pay-btn"
   style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;
          background:#2563eb;color:#fff;border-radius:12px;text-decoration:none;
          font-family:sans-serif;font-size:14px;font-weight:600;"&gt;
  Pay with Crypto
&lt;/a&gt;</code></pre>
                </div>
                <x-button type="button" variant="secondary" icon="copy" data-copy-target="snippet-redirect" class="mt-3">Copy code</x-button>
            </div>

            {{-- Option 2: API flow --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                <div class="mb-4 flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400">
                        <x-icon name="database" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="font-semibold text-slate-950 dark:text-white">Option 2 — Server-side API flow</p>
                        <p class="text-sm text-slate-500">Create invoices from your backend and redirect the customer. Recommended for all production stores.</p>
                    </div>
                </div>
                <div class="rounded-2xl bg-slate-950 p-4">
                    <pre id="snippet-api" class="overflow-x-auto text-xs text-slate-200"><code>// 1. Create invoice from your server
curl -X POST {{ rtrim(config('app.url'), '/') }}/api/v1/invoices \
  -H "Authorization: Bearer {{ $apiKey?->key_prefix ?? 'YOUR_API_KEY' }}..." \
  -H "Content-Type: application/json" \
  -d '{
    "amount": "0.001",
    "currency": "BTC",
    "order_id": "ORD-1048",
    "webhook_url": "https://your-site.com/webhooks/crynova"
  }'

// 2. Redirect customer to:
//    {{ rtrim(config('app.url'), '/') }}/pay/{uuid_from_response}</code></pre>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-button type="button" variant="secondary" icon="copy" data-copy-target="snippet-api">Copy</x-button>
                    <x-button href="{{ route('merchant.docs.index') }}" variant="ghost" icon="book">API docs →</x-button>
                </div>
            </div>

            {{-- Option 3: Payment link embed --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                <div class="mb-4 flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 dark:bg-violet-400/10 dark:text-violet-400">
                        <x-icon name="layout" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="font-semibold text-slate-950 dark:text-white">Option 3 — Payment link button</p>
                        <p class="text-sm text-slate-500">Paste your payment link URL into this snippet. No server code required.</p>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="fin-label">Your payment link URL</label>
                    <input type="text" id="pl-url-input" class="fin-input" placeholder="{{ rtrim(config('app.url'), '/') }}/pay/link/xxxx"
                           oninput="document.getElementById('pl-url-target').textContent=this.value||'YOUR_LINK_URL'">
                </div>
                <div class="rounded-2xl bg-slate-950 p-4">
                    <pre id="snippet-paylink" class="overflow-x-auto text-xs text-slate-200"><code>&lt;a href="<span id="pl-url-target">YOUR_LINK_URL</span>"
   style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;
          background:#7c3aed;color:#fff;border-radius:12px;text-decoration:none;
          font-family:sans-serif;font-size:14px;font-weight:600;"&gt;
  Pay with Crypto
&lt;/a&gt;</code></pre>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-button type="button" variant="secondary" icon="copy" data-copy-target="snippet-paylink">Copy code</x-button>
                    <x-button href="{{ route('merchant.payment-links.index') }}" variant="ghost" icon="link">Manage links →</x-button>
                </div>
            </div>
        </div>

        {{-- ── Preview + webhook note ────────────────────────────────── --}}
        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                <p class="mb-4 font-semibold text-slate-950 dark:text-white">Checkout preview</p>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900">
                    <div class="border-b border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                        <div class="flex items-center gap-2">
                            <x-logo variant="mark" class="h-6 w-6 rounded-lg shadow-none" />
                            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Crynova Checkout</span>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="text-xs text-slate-400">{{ $merchant->name }}</p>
                        <p class="mt-1 text-xl font-semibold text-slate-950 dark:text-white">0.001 <span class="text-blue-600">BTC</span></p>
                        <div class="mt-4 flex justify-center">
                            <div class="rounded-xl bg-white p-1.5 shadow-sm">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=bitcoin:example_address?amount=0.001"
                                     alt="preview QR" width="80" height="80" class="rounded-lg">
                            </div>
                        </div>
                        <div class="mt-3 rounded-xl bg-slate-100 px-3 py-2 text-center dark:bg-slate-800">
                            <p class="font-mono text-[10px] text-slate-500 break-all">bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                <p class="mb-3 font-semibold text-slate-950 dark:text-white">Webhook integration</p>
                <p class="text-sm text-slate-500 mb-4">Configure a webhook to receive instant payment notifications in your backend.</p>
                <div class="space-y-2 text-sm">
                    @php $events = ['invoice.paid', 'invoice.expired', 'invoice.underpaid', 'invoice.overpaid']; @endphp
                    @foreach($events as $ev)
                    <div class="flex items-center gap-2">
                        <x-icon name="check" class="h-4 w-4 text-emerald-500" />
                        <code class="text-xs text-slate-600 dark:text-slate-300">{{ $ev }}</code>
                    </div>
                    @endforeach
                </div>
                <x-button href="{{ route('merchant.webhooks.index') }}" variant="secondary" icon="link" class="mt-4">Configure webhooks →</x-button>
            </div>
        </div>
    </div>
</div>
@endsection
