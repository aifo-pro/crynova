@extends('layouts.app')
@section('title', 'Invoice ' . substr($invoice->uuid, 0, 8))

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('merchant.invoices.index') }}" class="text-slate-400 hover:text-white text-sm">← Invoices</a>
        <span class="text-slate-700">/</span>
        <span class="font-mono text-teal-200 text-sm">{{ substr($invoice->uuid, 0, 8) }}</span>
        <x-status-badge :status="$invoice->status" />
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_0.65fr]">
        <div class="space-y-6">
            <x-card title="Payment details">
                <div class="grid gap-3 sm:grid-cols-2 text-sm">
                    @foreach([
                        ['Amount', $invoice->amount . ' ' . $invoice->currency->code],
                        ['Received', $invoice->amount_received . ' ' . $invoice->currency->code],
                        ['Net (after fee)', $invoice->net_amount . ' ' . $invoice->currency->code],
                        ['Fee', $invoice->fee_percent . '% = ' . $invoice->fee_amount],
                        ['Pay address', $invoice->pay_address],
                        ['Network', $invoice->currency->network],
                        ['Confirmations required', $invoice->currency->confirmations_required],
                        ['Expires at', $invoice->expires_at?->format('Y-m-d H:i') ?? '—'],
                        ['Paid at', $invoice->paid_at?->format('Y-m-d H:i') ?? '—'],
                        ['Order ID', $invoice->order_id ?? '—'],
                    ] as [$label, $value])
                    <div class="rounded-lg border border-slate-800 bg-slate-900/50 px-4 py-3">
                        <dt class="text-xs text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 font-mono text-white break-all">{{ $value }}</dd>
                    </div>
                    @endforeach
                </div>
            </x-card>

            @if($invoice->transactions->count())
            <x-card title="Blockchain transactions">
                <x-table :headers="['TX Hash', 'Amount', 'Conf.', 'Status']">
                    @foreach($invoice->transactions as $tx)
                    <tr class="hover:bg-slate-900/60">
                        <td class="px-4 py-3 font-mono text-xs text-teal-200">{{ substr($tx->tx_hash, 0, 16) }}…</td>
                        <td class="px-4 py-3 font-mono">{{ $tx->amount }}</td>
                        <td class="px-4 py-3 text-sm {{ $tx->isConfirmed() ? 'text-teal-300' : 'text-amber-300' }}">
                            {{ $tx->confirmations }}/{{ $tx->confirmations_required }}
                        </td>
                        <td class="px-4 py-3"><x-status-badge :status="$tx->status" /></td>
                    </tr>
                    @endforeach
                </x-table>
            </x-card>
            @endif
        </div>

        <div class="space-y-6">
            <x-card title="Checkout link">
                <x-crypto-address-box id="checkout-url" label="Shareable payment URL"
                    :value="route('checkout.show', $invoice->uuid)" />
                <div class="mt-3">
                    <a href="{{ route('checkout.show', $invoice->uuid) }}" target="_blank">
                        <x-button variant="secondary" icon="arrow-right" class="w-full">Open checkout page</x-button>
                    </a>
                </div>
            </x-card>

            @if($invoice->webhookLogs->count())
            <x-card title="Webhook delivery">
                <div class="space-y-2">
                    @foreach($invoice->webhookLogs as $log)
                    <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/50 px-3 py-2 text-xs">
                        <span class="font-mono text-slate-300">{{ $log->event }}</span>
                        <span class="{{ $log->success ? 'text-teal-300' : 'text-rose-300' }}">
                            {{ $log->success ? '✓ ' . $log->http_status : '✗ ' . ($log->http_status ?? 'err') }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </x-card>
            @endif
        </div>
    </div>
</div>
@endsection
