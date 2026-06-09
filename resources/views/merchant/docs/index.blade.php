@extends('layouts.app')
@section('title', 'API Documentation')

@section('content')
<div class="space-y-6">
    <div><h1 class="text-3xl font-semibold text-white">API documentation</h1><p class="mt-1 text-slate-400">Invoice API, authentication and webhooks.</p></div>
    <x-card title="Authentication">
        <pre class="overflow-x-auto rounded-lg bg-black/60 p-4 text-sm text-slate-200"><code>Authorization: Bearer cn_live_your_api_key</code></pre>
    </x-card>
    <x-card title="Create invoice">
        <pre class="overflow-x-auto rounded-lg bg-black/60 p-4 text-sm text-slate-200"><code>POST /api/v1/invoices
{
  "amount": "149.00",
  "currency": "USDT_TRC20",
  "order_id": "ORD-1048",
  "description": "Order payment"
}</code></pre>
    </x-card>
    <x-card title="Webhook payload">
        <pre class="overflow-x-auto rounded-lg bg-black/60 p-4 text-sm text-slate-200"><code>{
  "event": "invoice.paid",
  "invoice_uuid": "...",
  "status": "paid",
  "amount_received": "149.00"
}</code></pre>
    </x-card>
</div>
@endsection
