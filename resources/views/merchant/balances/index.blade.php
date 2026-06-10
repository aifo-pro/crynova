@extends('layouts.app')
@section('title', 'Balances')

@section('content')
@php
    $formatCryptoAmount = function ($value): string {
        $value = (string) $value;
        if (str_contains($value, '.')) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        return $value === '' ? '0' : $value;
    };
@endphp
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-white">Balances</h1>
            <p class="mt-1 text-slate-400">Available, locked and settlement-ready funds.</p>
        </div>
        <x-button href="{{ route('merchant.withdrawals.index') }}" icon="banknote" variant="secondary">Request withdrawal</x-button>
    </div>

    {{-- Balance cards --}}
    @if($balances->isEmpty())
    <x-alert variant="info">No balances yet. Start accepting payments to see your balance here.</x-alert>
    @else
    <div class="metric-grid">
        @foreach($balances as $balance)
        <x-card>
            <div class="flex items-start justify-between gap-3">
                <div class="flex min-w-0 items-start gap-3">
                    <x-coin-icon :code="$balance->currency->code" class="h-10 w-10" />
                    <div class="min-w-0">
                    <p class="text-sm font-semibold text-teal-200">{{ $balance->currency->code }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $balance->currency->name }}</p>
                    <p class="mt-3 break-words text-2xl font-semibold text-white font-mono">{{ $formatCryptoAmount($balance->available) }} <span class="text-base font-medium text-slate-400">{{ $balance->currency->code }}</span></p>
                    </div>
                </div>
                <div class="rounded-lg border border-teal-400/20 bg-teal-400/10 p-2 text-teal-200">
                    <x-icon name="wallet" class="h-5 w-5" />
                </div>
            </div>
            @if($balance->locked > 0)
            <div class="mt-3 rounded-lg border border-amber-400/20 bg-amber-400/10 px-3 py-2 text-xs text-amber-200">
                {{ $formatCryptoAmount($balance->locked) }} {{ $balance->currency->code }} locked (withdrawal pending)
            </div>
            @endif
        </x-card>
        @endforeach
    </div>
    @endif

    {{-- Movement history --}}
    <x-card title="Balance movements" subtitle="Ledger of credits, debits and fees.">
        <x-table :headers="['Type', 'Currency', 'Amount', 'Before', 'After', 'Note', 'Date']">
            @forelse($movements as $mov)
            <tr class="hover:bg-slate-900/60">
                <td class="px-4 py-3">
                    @php
                        $typeColor = ['credit'=>'text-teal-300','debit'=>'text-rose-300','fee'=>'text-amber-300','refund'=>'text-blue-300','adjustment'=>'text-slate-300'];
                    @endphp
                    <span class="text-xs font-semibold {{ $typeColor[$mov->type] ?? 'text-slate-300' }}">{{ strtoupper($mov->type) }}</span>
                </td>
                <td class="px-4 py-3 font-semibold text-white">
                    <span class="inline-flex items-center gap-2">
                        <x-coin-icon :code="$mov->currency->code" class="h-6 w-6" />
                        {{ $mov->currency->code }}
                    </span>
                </td>
                <td class="px-4 py-3 font-mono {{ $mov->type === 'credit' ? 'text-teal-300' : 'text-rose-300' }}">
                    {{ $mov->type === 'credit' ? '+' : '-' }}{{ $formatCryptoAmount($mov->amount) }} {{ $mov->currency->code }}
                </td>
                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $mov->balance_before }}</td>
                <td class="px-4 py-3 font-mono text-xs text-slate-400">{{ $mov->balance_after }}</td>
                <td class="px-4 py-3 text-xs text-slate-400">{{ $mov->note ?? '—' }}</td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ $mov->created_at->diffForHumans() }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No movements yet.</td></tr>
            @endforelse
        </x-table>
        <div class="mt-4">{{ $movements->links() }}</div>
    </x-card>
</div>
@endsection
