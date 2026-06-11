@extends('layouts.app')
@section('title', 'API integration')

@section('content')
@php
    $baseUrl = rtrim(config('app.url'), '/');
    $apiBase = $baseUrl . '/api/v1';
    $apiKey = $merchant?->api_key;
    $displayKey = $apiKey ?: 'YOUR_API_KEY';
    $maskedKey = $merchant?->maskedApiKey() ?: 'Key is not created yet';
    $sampleCurrency = \App\Models\Currency::where('is_active', true)->orderBy('code')->value('code') ?? 'USDT';
    $webhookSecret = $merchant?->webhook_secret ?: 'whsec_your_secret';
@endphp

<div class="space-y-8">
    <div class="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-start sm:justify-between">
        <div>
            <x-badge variant="blue">{{ __('api.badge') }}</x-badge>
            <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950">{{ __('api.title') }}</h1>
            <p class="mt-3 max-w-3xl text-slate-600">
                {{ __('api.intro') }}
            </p>
        </div>
        @if($merchant)
            @include('account.integration._picker')
        @endif
    </div>

    @if(! $merchant)
        @include('account.integration._empty')
    @else
        <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-950">{{ $merchant->name }}</h2>
                <div class="mt-5 space-y-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase text-slate-400">API base URL</p>
                        <div class="mt-2 flex items-center gap-2">
                            <code class="min-w-0 flex-1 truncate font-mono text-sm text-slate-800">{{ $apiBase }}</code>
                            <button type="button" data-copy-text="{{ $apiBase }}" class="text-slate-400 hover:text-blue-600"><x-icon name="copy" class="h-4 w-4" /></button>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase text-slate-400">API key</p>
                        <div class="mt-2 flex items-center gap-2">
                            <code class="min-w-0 flex-1 truncate font-mono text-sm text-blue-600">{{ $maskedKey }}</code>
                            @if($apiKey)
                                <button type="button" data-copy-text="{{ $apiKey }}" class="text-slate-400 hover:text-blue-600"><x-icon name="copy" class="h-4 w-4" /></button>
                            @endif
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase text-slate-400">Shop ID</p>
                        <div class="mt-2 flex items-center gap-2">
                            <code class="min-w-0 flex-1 truncate font-mono text-sm text-slate-800">{{ $merchant->shop_id }}</code>
                            <button type="button" data-copy-text="{{ $merchant->shop_id }}" class="text-slate-400 hover:text-blue-600"><x-icon name="copy" class="h-4 w-4" /></button>
                        </div>
                    </div>
                </div>
                <div class="mt-5 flex flex-wrap gap-3">
                    @if($merchant->featuresUnlocked())
                        <x-button href="{{ route('merchant.api-keys.index', $merchant) }}" variant="secondary" icon="key">{{ __('api.api_keys') }}</x-button>
                        <x-button href="{{ route('merchant.webhooks.index', $merchant) }}" variant="secondary" icon="bell">{{ __('api.webhooks') }}</x-button>
                    @else
                        <x-button href="{{ route('merchant.settings.integration', $merchant) }}" variant="secondary" icon="key">{{ __('api.integration_settings') }}</x-button>
                    @endif
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-950">{{ __('api.quick_start') }}</h2>
                <ol class="mt-5 space-y-4 text-sm text-slate-600">
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">1</span><span>{{ __('api.step_1') }}</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">2</span><span>{{ __('api.step_2') }}</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">3</span><span>{{ __('api.step_3') }}</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">4</span><span>{{ __('api.step_4') }}</span></li>
                </ol>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">{{ __('api.endpoints') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('api.endpoints_note') }}</p>
                </div>
                <x-badge variant="green">Live API</x-badge>
            </div>
            <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Method</th>
                            <th class="px-4 py-3">Endpoint</th>
                            <th class="px-4 py-3">{{ __('api.purpose') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <tr><td class="px-4 py-3 font-mono text-emerald-600">GET</td><td class="px-4 py-3 font-mono">/api/v1/currencies</td><td class="px-4 py-3">{{ __('api.currencies_desc') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono text-emerald-600">GET</td><td class="px-4 py-3 font-mono">/api/v1/invoices</td><td class="px-4 py-3">{{ __('api.invoice_list_desc') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono text-blue-600">POST</td><td class="px-4 py-3 font-mono">/api/v1/invoices</td><td class="px-4 py-3">{{ __('api.invoice_create_desc') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono text-emerald-600">GET</td><td class="px-4 py-3 font-mono">/api/v1/invoices/{uuid}</td><td class="px-4 py-3">{{ __('api.invoice_show_desc') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono text-emerald-600">GET</td><td class="px-4 py-3 font-mono">/api/v1/invoices/{uuid}/status</td><td class="px-4 py-3">{{ __('api.invoice_status_desc') }}</td></tr>
                        <tr><td class="px-4 py-3 font-mono text-blue-600">POST</td><td class="px-4 py-3 font-mono">/api/v1/invoices/{uuid}/cancel</td><td class="px-4 py-3">{{ __('api.invoice_cancel_desc') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-card :title="__('api.create_invoice')" :subtitle="__('api.create_invoice_subtitle')">
                <div class="relative rounded-2xl bg-slate-950 p-4">
                    <button type="button" class="absolute right-3 top-3 rounded-lg bg-white/10 px-2 py-1 text-xs font-semibold text-white" data-copy-target="curl-create">Copy</button>
                    <pre id="curl-create" class="overflow-x-auto text-xs text-slate-200"><code>curl -X POST {{ $apiBase }}/invoices \
  -H "Authorization: Bearer {{ $displayKey }}" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: order-1048" \
  -d '{
    "amount": "25.00",
    "currency": "{{ $sampleCurrency }}",
    "order_id": "ORD-1048",
    "description": "Order #1048",
    "expires_in": 30,
    "metadata": {
      "customer_id": "42"
    }
  }'</code></pre>
                </div>
            </x-card>

            <x-card :title="__('api.api_response')" :subtitle="__('api.api_response_subtitle')">
                <div class="rounded-2xl bg-slate-950 p-4">
                    <pre class="overflow-x-auto text-xs text-slate-200"><code>{
  "invoice_id": "9ae4cd13-952a-4397-9966-ec4faf041721",
  "order_id": "ORD-1048",
  "status": "pending",
  "price_amount": "25",            // оригінальна ціна
  "price_currency": "{{ $sampleCurrency }}",  // валюта ціни (крипто або фіат)
  "pay_currency": "{{ $sampleCurrency }}",    // крипта для оплати (null поки не обрана)
  "currency": "{{ $sampleCurrency }}",
  "amount": "25.000000000000000000",
  "amount_received": "0.000000000000000000",
  "pay_address": "TRX9x...",
  "pay_memo": null,
  "description": "Order #1048",
  "metadata": { "customer_id": "42" },
  "net_amount": null,
  "paid_at": null,
  "expires_at": "2026-06-07T12:30:00+00:00",
  "checkout_url": "{{ $baseUrl }}/pay/9ae4cd13-952a-4397-9966-ec4faf041721",
  "transactions": []
}</code></pre>
                </div>
            </x-card>

            <x-card :title="__('api.status_check')" :subtitle="__('api.status_check_subtitle')">
                <div class="relative rounded-2xl bg-slate-950 p-4">
                    <button type="button" class="absolute right-3 top-3 rounded-lg bg-white/10 px-2 py-1 text-xs font-semibold text-white" data-copy-target="curl-status">Copy</button>
                    <pre id="curl-status" class="overflow-x-auto text-xs text-slate-200"><code>curl {{ $apiBase }}/invoices/{invoice_id}/status \
  -H "Authorization: Bearer {{ $displayKey }}"</code></pre>
                </div>
                <div class="mt-4 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                    <p><strong class="text-slate-950">pending</strong> - ожидает транзакцию.</p>
                    <p><strong class="text-slate-950">waiting_confirmations</strong> - транзакция найдена.</p>
                    <p><strong class="text-slate-950">paid</strong> - оплачено корректно.</p>
                    <p><strong class="text-slate-950">underpaid/overpaid</strong> - сумма отличается.</p>
                    <p><strong class="text-slate-950">expired</strong> - время истекло.</p>
                    <p><strong class="text-slate-950">failed/refunded</strong> - фінальний статус.</p>
                </div>
            </x-card>

            <x-card :title="__('api.webhook_signature')" :subtitle="__('api.webhook_signature_subtitle')">
                <div class="rounded-2xl bg-slate-950 p-4">
                    <pre class="overflow-x-auto text-xs text-slate-200"><code>const crypto = require('crypto');

function verifyCrynovaWebhook(rawBody, signature, secret) {
  const expected = 'sha256=' + crypto
    .createHmac('sha256', secret)
    .update(rawBody)
    .digest('hex');

  return crypto.timingSafeEqual(
    Buffer.from(signature),
    Buffer.from(expected)
  );
}</code></pre>
                </div>
                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    <p class="font-semibold text-slate-950">Headers</p>
                    <p class="mt-2 font-mono text-xs">X-Crynova-Event: invoice.paid</p>
                    <p class="font-mono text-xs">X-Crynova-Sig: sha256=...</p>
                    <p class="font-mono text-xs">X-Crynova-Delivery: 123</p>
                </div>
            </x-card>
        </div>

        <x-card :title="__('api.php_example')" :subtitle="__('api.php_example_subtitle')">
            <div class="relative rounded-2xl bg-slate-950 p-4">
                <button type="button" class="absolute right-3 top-3 rounded-lg bg-white/10 px-2 py-1 text-xs font-semibold text-white" data-copy-target="php-create">Copy</button>
                <pre id="php-create" class="overflow-x-auto text-xs text-slate-200"><code>$payload = [
    'amount' => '25.00',
    'currency' => '{{ $sampleCurrency }}',
    'order_id' => 'ORD-1048',
    'description' => 'Order #1048',
];

$ch = curl_init('{{ $apiBase }}/invoices');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer {{ $displayKey }}',
        'Content-Type: application/json',
        'Idempotency-Key: ORD-1048',
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
]);

$response = json_decode(curl_exec($ch), true);
header('Location: ' . $response['checkout_url']);</code></pre>
            </div>
        </x-card>

        {{-- ── Аутентификация ─────────────────────────────────────── --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">Аутентификация</h2>
            <p class="mt-1 text-sm text-slate-500">Ключ передається одним із трьох способів (рекомендується заголовок Authorization):</p>
            <div class="mt-4 rounded-2xl bg-slate-950 p-4">
                <pre class="overflow-x-auto text-xs text-slate-200"><code>Authorization: Bearer {{ $displayKey }}
# или
X-Api-Key: {{ $displayKey }}
# или (нежелательно) ?api_key={{ $displayKey }}</code></pre>
            </div>
            <p class="mt-3 text-sm text-slate-500">Усі відповіді — у форматі JSON. Базовий URL: <code class="font-mono text-blue-600">{{ $apiBase }}</code></p>
        </div>

        {{-- ── Параметри створення рахунку ──────────────────────────── --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">Параметри запиту · POST /invoices</h2>
            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr><th class="px-4 py-3">Поле</th><th class="px-4 py-3">Тип</th><th class="px-4 py-3">Обяз.</th><th class="px-4 py-3">Описание</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <tr><td class="px-4 py-3 font-mono">currency</td><td class="px-4 py-3">string</td><td class="px-4 py-3 text-rose-500">так</td><td class="px-4 py-3"><b>Крипто-код</b> з <code>/currencies</code> (напр. <code>{{ $sampleCurrency }}</code>) — пряма оплата в крипті, <b>або фіат-код</b> (USD, EUR, UAH, PLN…) — рахунок у фіаті, клієнт обере крипту на сторінці оплати.</td></tr>
                        <tr><td class="px-4 py-3 font-mono">amount</td><td class="px-4 py-3">string|number</td><td class="px-4 py-3 text-rose-500">так</td><td class="px-4 py-3">Сума рахунку у вказаній валюті (крипто або фіат).</td></tr>
                        <tr><td class="px-4 py-3 font-mono">order_id</td><td class="px-4 py-3">string</td><td class="px-4 py-3 text-slate-400">ні</td><td class="px-4 py-3">Ваш идентификатор заказа (до 255 символов).</td></tr>
                        <tr><td class="px-4 py-3 font-mono">description</td><td class="px-4 py-3">string</td><td class="px-4 py-3 text-slate-400">ні</td><td class="px-4 py-3">Описание (до 1000 символов).</td></tr>
                        <tr><td class="px-4 py-3 font-mono">expires_in</td><td class="px-4 py-3">integer</td><td class="px-4 py-3 text-slate-400">ні</td><td class="px-4 py-3">TTL рахунку у хвилинах (5–1440). За замовчуванням із налаштувань.</td></tr>
                        <tr><td class="px-4 py-3 font-mono">metadata</td><td class="px-4 py-3">object</td><td class="px-4 py-3 text-slate-400">ні</td><td class="px-4 py-3">Довільні рядкові пари, повертаються у вебхуку.</td></tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-sm text-slate-500">Заголовок <code class="font-mono text-blue-600">Idempotency-Key</code> (опц.) робить повторний POST безпечним — повернеться перша відповідь протягом 24г.</p>
        </div>

        {{-- ── Фіатні рахунки ──────────────────────────────────────── --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">Рахунки у фіатній валюті</h2>
            <p class="mt-3 text-sm leading-6 text-slate-500">
                Передайте у полі <code class="font-mono text-blue-600">currency</code> код <b>фіатної</b> валюти — тоді рахунок створюється у фіаті, а <b>клієнт сам обирає криптовалюту</b> на сторінці оплати. Сума автоматично конвертується за поточним курсом і фіксується після вибору.
            </p>
            <p class="mt-3 text-sm font-semibold text-slate-700">Підтримувані фіатні валюти:</p>
            <p class="mt-1 font-mono text-xs leading-5 text-slate-500">{{ implode(', ', (array) config('crynova.fiat_currencies')) }}</p>

            <p class="mt-4 text-sm font-semibold text-slate-700">Приклад: рахунок на 499 UAH</p>
            <pre class="mt-2 overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs leading-5 text-slate-100"><code>POST {{ $baseUrl }}/invoices
Authorization: Bearer &lt;API_KEY&gt;
Content-Type: application/json

{
  "currency": "UAH",
  "amount": "499.00",
  "order_id": "ORDER-1001",
  "description": "Підписка Pro"
}</code></pre>
            <p class="mt-3 text-sm font-semibold text-slate-700">У відповіді (фіатний рахунок):</p>
            <pre class="mt-2 overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs leading-5 text-slate-100"><code>{
  "invoice_id": "9ae4cd13-...",
  "status": "pending",
  "price_amount": "499",
  "price_currency": "UAH",
  "pay_currency": null,          // крипту ще не обрано
  "amount": null,                // буде заповнено після вибору крипти
  "pay_address": null,
  "checkout_url": "{{ url('/pay/9ae4cd13-...') }}"
}</code></pre>
            <ul class="mt-3 space-y-1.5 text-sm text-slate-600">
                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 text-emerald-500" /> Перенаправте клієнта на <code class="font-mono text-blue-600">checkout_url</code> — він обере крипту, сума сконвертується.</li>
                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 text-emerald-500" /> Після вибору крипти у вебхуках/відповіді з’являться <code>pay_currency</code>, <code>amount</code>, <code>pay_address</code>.</li>
                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 text-emerald-500" /> Поля <code>price_amount</code>/<code>price_currency</code> завжди містять оригінальну (фіатну) ціну для звірки.</li>
            </ul>
        </div>

        {{-- ── Валюти + список + скасування ──────────────────────────── --}}
        <div class="grid gap-6 lg:grid-cols-2">
            <x-card title="Список валют · GET /currencies">
                <div class="relative rounded-2xl bg-slate-950 p-4">
                    <button type="button" class="absolute right-3 top-3 rounded-lg bg-white/10 px-2 py-1 text-xs font-semibold text-white" data-copy-target="curl-cur">Copy</button>
                    <pre id="curl-cur" class="overflow-x-auto text-xs text-slate-200"><code>curl {{ $apiBase }}/currencies \
  -H "Authorization: Bearer {{ $displayKey }}"</code></pre>
                </div>
                <div class="mt-3 rounded-2xl bg-slate-950 p-4">
                    <pre class="overflow-x-auto text-xs text-slate-200"><code>{
  "data": [{
    "code": "{{ $sampleCurrency }}",
    "name": "Tether USD",
    "network": "tron",
    "contract_address": "TR7NHq...",
    "decimals": 6,
    "confirmations_required": 19,
    "min_amount": "1",
    "max_amount": null,
    "estimated_fee": "1.4",
    "supports_memo": false
  }],
  "fiat": ["USD","EUR","GBP","UAH","PLN","KZT", ...]  // коди для фіатних рахунків
}</code></pre>
                </div>
            </x-card>

            <x-card title="Список / отмена счетов">
                <div class="relative rounded-2xl bg-slate-950 p-4">
                    <button type="button" class="absolute right-3 top-3 rounded-lg bg-white/10 px-2 py-1 text-xs font-semibold text-white" data-copy-target="curl-list">Copy</button>
                    <pre id="curl-list" class="overflow-x-auto text-xs text-slate-200"><code># список с фильтрами
curl "{{ $apiBase }}/invoices?status=paid&per_page=50" \
  -H "Authorization: Bearer {{ $displayKey }}"

# скасування неоплаченого рахунку
curl -X POST {{ $apiBase }}/invoices/{invoice_id}/cancel \
  -H "Authorization: Bearer {{ $displayKey }}"</code></pre>
                </div>
                <p class="mt-3 text-sm text-slate-500">Фільтри списку: <code>status</code>, <code>order_id</code>, <code>currency</code>, <code>per_page</code> (1–100). Скасувати можна лише <code>pending</code>/<code>waiting_confirmations</code> без надходжень.</p>
            </x-card>
        </div>

        {{-- ── Події та payload вебхука ─────────────────────────── --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">Вебхуки — події та payload</h2>
            <div class="mt-4 grid gap-6 lg:grid-cols-2">
                <div>
                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Подія</th><th class="px-4 py-3">Коли</th></tr></thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr><td class="px-4 py-3 font-mono">invoice.created</td><td class="px-4 py-3">Рахунок створено.</td></tr>
                                <tr><td class="px-4 py-3 font-mono">invoice.waiting_confirmations</td><td class="px-4 py-3">Транзакция в мемпуле/блоке.</td></tr>
                                <tr><td class="px-4 py-3 font-mono">invoice.paid</td><td class="px-4 py-3">Оплачено полностью.</td></tr>
                                <tr><td class="px-4 py-3 font-mono">invoice.underpaid</td><td class="px-4 py-3">Отримано менше суми.</td></tr>
                                <tr><td class="px-4 py-3 font-mono">invoice.overpaid</td><td class="px-4 py-3">Отримано більше суми.</td></tr>
                                <tr><td class="px-4 py-3 font-mono">invoice.expired</td><td class="px-4 py-3">Час вийшов / скасовано.</td></tr>
                                <tr><td class="px-4 py-3 font-mono">invoice.refunded</td><td class="px-4 py-3">Виконано повернення.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-3 text-sm text-slate-500">Повтори у разі помилки: 5хв → 30хв → 2г → 8г → 24г. Відповідайте <code>2xx</code> для підтвердження.</p>
                </div>
                <div class="rounded-2xl bg-slate-950 p-4">
                    <pre class="overflow-x-auto text-xs text-slate-200"><code>POST {ваш callback_url}
X-Crynova-Event: invoice.paid
X-Crynova-Sig: sha256=...
X-Crynova-Delivery: 123

{
  "event": "invoice.paid",
  "invoice_id": "9ae4cd13-...",
  "order_id": "ORD-1048",
  "status": "paid",
  "price_amount": "25",
  "price_currency": "{{ $sampleCurrency }}",
  "pay_currency": "{{ $sampleCurrency }}",
  "currency": "{{ $sampleCurrency }}",
  "amount": "25.00",
  "received": "25.00",
  "address": "TRX9x...",
  "paid_at": "2026-06-07T12:31:00+00:00",
  "metadata": { "customer_id": "42" },
  "created_at": "2026-06-07T12:00:00+00:00"
}</code></pre>
                </div>
            </div>
        </div>

        {{-- ── Коди помилок та ліміти ──────────────────────────────── --}}
        <div class="grid gap-6 lg:grid-cols-2">
            <x-card title="Коди помилок">
                <div class="overflow-hidden rounded-2xl border border-slate-200">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Код</th><th class="px-4 py-3">Значение</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr><td class="px-4 py-3 font-mono">401</td><td class="px-4 py-3">Невірний або відсутній API-ключ.</td></tr>
                            <tr><td class="px-4 py-3 font-mono">403</td><td class="px-4 py-3">У ключа нет нужного permission / IP не в whitelist.</td></tr>
                            <tr><td class="px-4 py-3 font-mono">404</td><td class="px-4 py-3">Рахунок не знайдено.</td></tr>
                            <tr><td class="px-4 py-3 font-mono">422</td><td class="px-4 py-3">Ошибка валидации / конфликт Idempotency-Key.</td></tr>
                            <tr><td class="px-4 py-3 font-mono">429</td><td class="px-4 py-3">Перевищено ліміт запитів.</td></tr>
                        </tbody>
                    </table>
                </div>
            </x-card>
            <x-card title="Ліміти та права">
                <ul class="space-y-2 text-sm text-slate-600">
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 text-emerald-500" /> Rate limit: 60 запросов/мин на ключ (заголовки <code>X-RateLimit-*</code>).</li>
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 text-emerald-500" /> Permissions ключа: <code>invoices.create</code>, <code>invoices.read</code>, <code>invoices.cancel</code>, <code>currencies.read</code>.</li>
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 text-emerald-500" /> Опціональний IP-whitelist на ключ.</li>
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 text-emerald-500" /> Усі суми — рядки з точністю до 18 знаків (без float).</li>
                </ul>
            </x-card>
        </div>

        <div class="rounded-3xl border border-blue-100 bg-blue-50 p-6 text-sm text-blue-900">
            <p class="font-semibold">{{ __('api.production_title') }}</p>
            <p class="mt-2">{{ __('api.production_text') }}</p>
        </div>
    @endif
</div>
@endsection
