@extends('layouts.app')
@section('title', 'Invoice ' . substr($invoice->uuid, 0, 8))

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.invoices.index') }}" class="text-slate-400 hover:text-white text-sm">← Рахунки</a>
        <span class="text-slate-700">/</span>
        <span class="font-mono text-teal-200 text-sm">{{ $invoice->uuid }}</span>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_0.7fr]">
        <div class="space-y-6">
            <x-card title="Деталі рахунку">
                <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                    @foreach([
                        ['Мерчант', $invoice->merchant->name],
                        ['Валюта', $invoice->currency->code],
                        ['Сума', $invoice->amount . ' ' . $invoice->currency->code],
                        ['Отримано', $invoice->amount_received . ' ' . $invoice->currency->code],
                        ['Адреса оплати', $invoice->pay_address],
                        ['Статус', null],
                        ['Order ID', $invoice->order_id ?? '—'],
                        ['Діє до', $invoice->expires_at?->format('Y-m-d H:i') ?? '—'],
                        ['Оплачено', $invoice->paid_at?->format('Y-m-d H:i') ?? '—'],
                        ['Комісія %', $invoice->fee_percent . '%'],
                        ['Сума комісії', $invoice->fee_amount],
                        ['До зарахування', $invoice->net_amount],
                    ] as [$label, $value])
                    <div class="rounded-lg border border-slate-800 bg-slate-900/50 px-4 py-3">
                        <dt class="text-xs text-slate-500">{{ $label }}</dt>
                        @if($label === 'Статус')
                            <dd class="mt-1"><x-status-badge :status="$invoice->status" /></dd>
                        @else
                            <dd class="mt-1 font-mono text-white break-all">{{ $value }}</dd>
                        @endif
                    </div>
                    @endforeach
                </dl>
            </x-card>

            <x-card title="Блокчейн-транзакції ({{ $invoice->transactions->count() }})">
                <x-table :headers="['TX Hash', 'Сума', 'Підтвердження', 'Статус', 'Час']">
                    @forelse($invoice->transactions as $tx)
                    <tr class="hover:bg-slate-900/60">
                        <td class="px-4 py-3 font-mono text-xs text-teal-200">{{ substr($tx->tx_hash, 0, 16) }}…</td>
                        <td class="px-4 py-3 font-mono">{{ $tx->amount }}</td>
                        <td class="px-4 py-3">{{ $tx->confirmations }} / {{ $tx->confirmations_required }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$tx->status" /></td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $tx->created_at->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Транзакцій ще немає.</td></tr>
                    @endforelse
                </x-table>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Доставка webhook">
                <x-table :headers="['Подія', 'Статус', 'Спроба']">
                    @forelse($invoice->webhookLogs as $log)
                    <tr class="hover:bg-slate-900/60">
                        <td class="px-4 py-3 font-mono text-xs">{{ $log->event }}</td>
                        <td class="px-4 py-3">
                            @if($log->success)
                                <span class="text-teal-300 text-xs">✓ {{ $log->http_status }}</span>
                            @else
                                <span class="text-rose-300 text-xs">✗ {{ $log->http_status ?? 'err' }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-400">{{ $log->attempt }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">Webhook не надсилались.</td></tr>
                    @endforelse
                </x-table>
            </x-card>

            <x-card title="Швидкі посилання">
                <div class="space-y-3">
                    <a href="{{ route('checkout.show', $invoice->uuid) }}" target="_blank"
                       class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/50 px-4 py-3 text-sm text-teal-200 hover:text-white">
                        Сторінка оплати <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                    <a href="{{ route('admin.merchants.show', $invoice->merchant) }}"
                       class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/50 px-4 py-3 text-sm text-slate-300 hover:text-white">
                        Профіль мерчанта <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection
