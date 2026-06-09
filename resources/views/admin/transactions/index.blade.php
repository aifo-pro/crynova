@extends('layouts.app')
@section('title', 'Блокчейн-транзакції')

@section('content')
<div class="space-y-6">
    <div>
        <x-badge variant="blue">Адмін-панель</x-badge>
        <h1 class="mt-3 text-3xl font-semibold text-white">Блокчейн-транзакції</h1>
        <p class="mt-1 text-slate-400">Відстеження платежів у мережі та моніторинг підтверджень.</p>
    </div>

    <x-card>
        <form method="GET" class="grid gap-3 md:grid-cols-3">
            <input name="search" value="{{ request('search') }}" class="fin-input md:col-span-2" placeholder="TX hash або адреса…">
            <select name="status" class="fin-input">
                <option value="">Статус: усі</option>
                @foreach(['pending'=>'Очікує','confirming'=>'Підтверджується','confirmed'=>'Підтверджено','failed'=>'Помилка'] as $s=>$lbl)
                    <option value="{{ $s }}" @selected(request('status') == $s)>{{ $lbl }}</option>
                @endforeach
            </select>
            <x-button type="submit" variant="secondary">Фільтр</x-button>
        </form>
    </x-card>

    <x-card>
        <x-table :headers="['TX Hash', 'Мерчант / Рахунок', 'Валюта', 'Сума', 'Підтвердження', 'Статус', 'Час']">
            @forelse($txs as $tx)
            <tr class="hover:bg-slate-900/60">
                <td class="px-4 py-3 font-mono text-xs text-teal-200" title="{{ $tx->tx_hash }}">
                    {{ substr($tx->tx_hash, 0, 14) }}…
                </td>
                <td class="px-4 py-3 text-sm">
                    @if($tx->invoice)
                        <p class="text-slate-300">{{ $tx->invoice->merchant->name ?? '—' }}</p>
                        <p class="font-mono text-xs text-slate-500">{{ substr($tx->invoice->uuid, 0, 8) }}</p>
                    @else
                        <span class="text-slate-500">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 font-semibold">{{ $tx->currency->code }}</td>
                <td class="px-4 py-3 font-mono">{{ $tx->amount }}</td>
                <td class="px-4 py-3">
                    <span class="{{ $tx->isConfirmed() ? 'text-teal-300' : 'text-amber-300' }}">
                        {{ $tx->confirmations }} / {{ $tx->confirmations_required }}
                    </span>
                </td>
                <td class="px-4 py-3"><x-status-badge :status="$tx->status" /></td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ $tx->created_at->diffForHumans() }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">Транзакцій не знайдено.</td></tr>
            @endforelse
        </x-table>
        <div class="mt-4">{{ $txs->links() }}</div>
    </x-card>
</div>
@endsection
