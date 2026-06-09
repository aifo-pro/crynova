@extends('layouts.app')
@section('title', 'Withdrawals')

@section('content')
@php($currencies = \App\Models\Currency::where('is_active', true)->orderBy('code')->get())
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-white">Withdrawals</h1>
        <p class="mt-1 text-slate-400">Request payouts from available balances. Admin approval is required.</p>
    </div>

    <x-card title="New withdrawal">
        <form method="POST" action="{{ route('merchant.withdrawals.store') }}" class="grid gap-4 md:grid-cols-2">
            @csrf
            <div>
                <label class="fin-label">Currency</label>
                <select name="currency_id" class="fin-input" required>
                    @foreach($currencies as $currency)<option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->network }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="fin-label">Amount</label>
                <input name="amount" class="fin-input" placeholder="0.00000000" required>
            </div>
            <div class="md:col-span-2">
                <label class="fin-label">Destination address</label>
                <input name="to_address" class="fin-input" placeholder="Wallet address" required>
            </div>
            <div class="md:col-span-2">
                <label class="fin-label">Memo / Tag</label>
                <input name="memo" class="fin-input" placeholder="Optional">
            </div>
            <x-button type="submit" class="md:col-span-2" icon="banknote">Submit request</x-button>
        </form>
    </x-card>

    <x-card title="Withdrawal history">
        <x-table :headers="['UUID', 'Currency', 'Amount', 'Address', 'Status', 'Created']">
            @forelse($withdrawals as $wd)
                <tr class="hover:bg-slate-900/60">
                    <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ substr($wd->uuid, 0, 8) }}</td>
                    <td class="px-4 py-3">{{ $wd->currency->code }}</td>
                    <td class="px-4 py-3 font-mono">{{ $wd->amount }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-400">{{ substr($wd->to_address, 0, 18) }}...</td>
                    <td class="px-4 py-3"><x-status-badge :status="$wd->status" /></td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $wd->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No withdrawals yet.</td></tr>
            @endforelse
        </x-table>
        <div class="mt-4">{{ $withdrawals->links() }}</div>
    </x-card>
</div>
@endsection
