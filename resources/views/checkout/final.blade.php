<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('checkout.invoice') }} {{ $invoice->status }} - Crynova</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10 text-slate-950">
@php
    $icons = [
        'paid' => ['check', __('checkout.payment_confirmed'), 'green'],
        'underpaid' => ['bell', __('checkout.underpaid'), 'yellow'],
        'overpaid' => ['wallet', __('checkout.overpaid'), 'blue'],
        'expired' => ['clock', __('checkout.invoice_expired'), 'slate'],
        'failed' => ['x', __('checkout.payment_failed'), 'red'],
        'refunded' => ['arrow-right', __('checkout.refunded'), 'slate'],
    ];
    [$icon, $label, $variant] = $icons[$invoice->status] ?? ['bell', ucfirst($invoice->status), 'slate'];
@endphp
<x-card class="w-full max-w-md text-center">
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-blue-100 bg-blue-50 text-blue-600">
        <x-icon :name="$icon" class="h-8 w-8" />
    </div>
    <h1 class="mt-5 text-2xl font-semibold text-slate-950">{{ $label }}</h1>
    <p class="mt-2 text-sm text-slate-500">
        {{ __('checkout.invoice') }} #{{ substr($invoice->uuid, 0, 8) }}
        @if($invoice->order_id) · {{ __('checkout.order_id') }} {{ $invoice->order_id }} @endif
    </p>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-left text-sm">
        <div class="flex justify-between gap-4 py-2">
            <span class="text-slate-500">{{ __('checkout.expected') }}</span>
            <span class="font-mono text-slate-950">{{ $invoice->amount }} {{ $invoice->currency->code }}</span>
        </div>
        <div class="flex justify-between gap-4 border-t border-slate-200 py-2">
            <span class="text-slate-500">{{ __('checkout.received') }}</span>
            <span class="font-mono text-slate-950">{{ $invoice->amount_received }} {{ $invoice->currency->code }}</span>
        </div>
        @if($invoice->paid_at)
            <div class="flex justify-between gap-4 border-t border-slate-200 py-2">
                <span class="text-slate-500">{{ __('checkout.paid_at') }}</span>
                <span class="text-slate-950">{{ $invoice->paid_at->format('Y-m-d H:i') }} UTC</span>
            </div>
        @endif
    </div>
    <p class="mt-6 text-xs text-slate-500">{{ __('checkout.powered_by') }}</p>
</x-card>
</body>
</html>
