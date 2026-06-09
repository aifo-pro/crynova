@extends('layouts.app')
@section('title', 'Invoices')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-white">Invoices</h1>
            <p class="mt-1 text-slate-400">Search, filter and inspect payment requests.</p>
        </div>
        <x-button href="{{ route('merchant.invoices.create') }}" icon="credit-card">Create invoice</x-button>
    </div>
    <x-card>
        <form method="GET" class="grid gap-3 md:grid-cols-4">
            <input name="search" value="{{ request('search') }}" class="fin-input md:col-span-2" placeholder="Search by order ID or invoice UUID…">
            <select name="status" class="fin-input">
                <option value="">Status: all</option>
                @foreach(['pending','waiting_confirmations','paid','underpaid','overpaid','expired','failed','refunded'] as $s)
                    <option value="{{ $s }}" @selected(request('status') == $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <select name="currency" class="fin-input">
                <option value="">Currency: all</option>
                @foreach($currencies as $c)
                    <option value="{{ $c->code }}" @selected(request('currency') == $c->code)>{{ $c->code }}</option>
                @endforeach
            </select>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </form>
    </x-card>
    <x-card>
        <x-table :headers="['Invoice', 'Order', 'Currency', 'Amount', 'Status', 'Created', '']">
            @forelse($invoices as $inv)
            <tr class="hover:bg-slate-900/60">
                <td class="px-4 py-3 font-mono text-xs text-teal-200">{{ substr($inv->uuid, 0, 8) }}…</td>
                <td class="px-4 py-3 text-sm text-slate-400">{{ $inv->order_id ?? '—' }}</td>
                <td class="px-4 py-3 font-semibold text-white">{{ $inv->currency->code }}</td>
                <td class="px-4 py-3 font-mono">{{ $inv->amount }}</td>
                <td class="px-4 py-3"><x-status-badge :status="$inv->status" /></td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ $inv->created_at->diffForHumans() }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('merchant.invoices.show', $inv->uuid) }}" class="text-sm text-teal-200 hover:text-white">View</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">No invoices yet. <a href="{{ route('merchant.invoices.create') }}" class="text-teal-300 hover:text-white">Create one</a>.</td></tr>
            @endforelse
        </x-table>
        <div class="mt-4">{{ $invoices->links() }}</div>
    </x-card>
</div>
@endsection
