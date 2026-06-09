@extends('layouts.app')
@section('title', 'Refunds')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-950 dark:text-white">Refunds</h1>
        <p class="mt-1 text-slate-500">Request and track customer refunds. All refunds are reviewed by admin before processing.</p>
    </div>
    <x-alert variant="warning" title="How refunds work">
        Submit a refund request with the destination wallet address. Admin will verify and broadcast the transaction.
        Crypto refunds are irreversible — double-check the address before submitting.
    </x-alert>

    {{-- ── Request form ───────────────────────────────────────────────── --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
        <p class="mb-4 font-semibold text-slate-950 dark:text-white">New refund request</p>
        <form method="POST" action="{{ route('merchant.refunds.store') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            <div>
                <label class="fin-label">Invoice ID <span class="text-rose-400">*</span></label>
                <input name="invoice_id" type="number" class="fin-input @error('invoice_id') border-rose-500 @enderror"
                       placeholder="Invoice ID (must be paid)" value="{{ old('invoice_id') }}" required>
                @error('invoice_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-slate-400">Only paid invoices can be refunded.</p>
            </div>
            <div>
                <label class="fin-label">Refund amount <span class="text-rose-400">*</span></label>
                <input name="amount" type="number" step="any" min="0" class="fin-input @error('amount') border-rose-500 @enderror"
                       placeholder="0.00" value="{{ old('amount') }}" required>
                @error('amount')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="fin-label">Destination wallet address <span class="text-rose-400">*</span></label>
                <input name="to_address" type="text" class="fin-input font-mono @error('to_address') border-rose-500 @enderror"
                       placeholder="bc1q..." value="{{ old('to_address') }}" required>
                @error('to_address')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="fin-label">Memo / Tag <span class="text-slate-400">(if required)</span></label>
                <input name="memo" type="text" class="fin-input" placeholder="Leave empty if not needed" value="{{ old('memo') }}">
            </div>
            <div class="sm:col-span-2">
                <label class="fin-label">Reason for refund <span class="text-slate-400">(optional)</span></label>
                <textarea name="reason" rows="2" class="fin-input"
                          placeholder="Customer requested refund, duplicate charge, etc.">{{ old('reason') }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <x-button type="submit" icon="banknote">Submit refund request</x-button>
            </div>
        </form>
    </div>

    {{-- ── Refunds list ────────────────────────────────────────────────── --}}
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <p class="font-semibold text-slate-950 dark:text-white">Refund history</p>
            <form method="GET">
                <select name="status" class="fin-input py-1 text-xs w-36" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach(['pending','approved','processing','completed','rejected','failed'] as $s)
                        <option value="{{ $s }}" @selected(request('status') == $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">UUID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Invoice</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Currency</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wide">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($refunds as $refund)
                    @php
                        $sColors = [
                            'pending'    => 'text-amber-600 dark:text-amber-400',
                            'approved'   => 'text-blue-600 dark:text-blue-400',
                            'processing' => 'text-violet-600 dark:text-violet-400',
                            'completed'  => 'text-emerald-600 dark:text-emerald-400',
                            'rejected'   => 'text-rose-600 dark:text-rose-400',
                            'failed'     => 'text-rose-600 dark:text-rose-400',
                        ];
                    @endphp
                    <tr class="border-b border-slate-50 hover:bg-slate-50/80 dark:border-slate-800/60 dark:hover:bg-slate-800/40">
                        <td class="px-6 py-3 font-mono text-xs text-blue-600">{{ substr($refund->uuid, 0, 8) }}…</td>
                        <td class="px-4 py-3 text-xs text-slate-500">#{{ $refund->invoice_id }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-950 dark:text-white">{{ $refund->currency->code }}</td>
                        <td class="px-4 py-3 text-right font-mono text-slate-700 dark:text-slate-300">{{ $refund->amount }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-semibold">{{ ucfirst($refund->type) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-semibold {{ $sColors[$refund->status] ?? 'text-slate-400' }}">
                                {{ ucfirst($refund->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-400">{{ $refund->created_at->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400">No refund requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($refunds->hasPages())
        <div class="border-t border-slate-100 px-6 py-4 dark:border-slate-800">
            {{ $refunds->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
