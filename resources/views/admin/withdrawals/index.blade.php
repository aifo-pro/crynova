@extends('layouts.app')
@section('title', 'Виплати')

@section('content')
@php
    $pendingIds = $withdrawals->getCollection()->filter(fn ($w) => $w->isPending())->pluck('id')->values();
@endphp
<div class="space-y-6" x-data="{
        selected: [],
        pending: @js($pendingIds),
        reason: '',
        get allChecked() { return this.pending.length > 0 && this.selected.length === this.pending.length; },
        toggleAll(e) { this.selected = e.target.checked ? [...this.pending] : []; },
        submit(action) {
            if (this.selected.length === 0) return;
            if (action === 'reject' && !this.reason.trim()) { alert('Вкажіть причину відхилення.'); return; }
            if (! confirm(action === 'approve' ? 'Схвалити обрані виплати?' : 'Відхилити обрані виплати?')) return;
            this.$refs.bulkAction.value = action;
            this.$refs.bulkForm.submit();
        }
    }">
    <div><h1 class="text-3xl font-semibold text-white">Виплати</h1><p class="mt-1 text-slate-400">Схвалення або відхилення запитів мерчантів на виплати.</p></div>

    {{-- Hidden bulk form: ids are mirrored from the Alpine selection. --}}
    <form x-ref="bulkForm" method="POST" action="{{ route('admin.withdrawals.bulk') }}" class="hidden">
        @csrf
        <input type="hidden" name="action" x-ref="bulkAction">
        <input type="hidden" name="reason" :value="reason">
        <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
    </form>

    {{-- Bulk action bar --}}
    <div x-show="selected.length" x-cloak
         class="flex flex-wrap items-center gap-3 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 shadow-sm">
        <span class="text-sm font-black text-blue-700"><span x-text="selected.length"></span> обрано</span>
        <input x-model="reason" placeholder="Причина (для відхилення)"
               class="fin-input min-w-56 flex-1 bg-white">
        <button type="button" @click="submit('approve')"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-blue-700">
            <x-icon name="check" class="h-4 w-4" /> Схвалити обрані
        </button>
        <button type="button" @click="submit('reject')"
                class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-bold text-rose-600 transition hover:bg-rose-50">
            <x-icon name="x" class="h-4 w-4" /> Відхилити обрані
        </button>
    </div>

    <template x-if="pending.length">
        <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-500">
            <input type="checkbox" :checked="allChecked" @change="toggleAll($event)" class="rounded border-slate-300 text-blue-600">
            Обрати всі очікуючі (<span x-text="pending.length"></span>)
        </label>
    </template>

    <x-card>
        <x-table :headers="['', 'Мерчант', 'Валюта', 'Сума', 'Призначення', 'Статус', 'Дії']">
            @forelse($withdrawals as $wd)
                <tr class="hover:bg-slate-900/60 align-top">
                    <td class="px-4 py-3">
                        @if($wd->isPending())
                            <input type="checkbox" value="{{ $wd->id }}" x-model="selected"
                                   class="rounded border-slate-300 text-blue-600">
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $wd->merchant->name }}</td>
                    <td class="px-4 py-3">{{ $wd->currency->code }}</td>
                    <td class="px-4 py-3 font-mono">{{ $wd->amount }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-400">{{ substr($wd->to_address, 0, 22) }}...</td>
                    <td class="px-4 py-3"><x-status-badge :status="$wd->status" /></td>
                    <td class="px-4 py-3">
                        @if($wd->isPending())
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('admin.withdrawals.approve', $wd) }}">
                                    @csrf
                                    <x-button type="submit" variant="primary">Схвалити</x-button>
                                </form>
                                <form method="POST" action="{{ route('admin.withdrawals.reject', $wd) }}" class="flex gap-2">
                                    @csrf
                                    <input name="reason" class="fin-input min-w-40" placeholder="Причина" required>
                                    <x-button type="submit" variant="danger">Відхилити</x-button>
                                </form>
                            </div>
                        @elseif(in_array($wd->status, ['approved', 'processing'], true))
                            <form method="POST" action="{{ route('admin.withdrawals.sent', $wd) }}" class="flex flex-wrap gap-2">
                                @csrf
                                <input name="tx_hash" class="fin-input min-w-48 font-mono text-xs" placeholder="TX hash" required>
                                <x-button type="submit" variant="primary">Позначити відправленим</x-button>
                            </form>
                        @elseif(in_array($wd->status, ['sent', 'confirmed'], true))
                            <span class="font-mono text-xs text-emerald-600">{{ $wd->tx_hash ? substr($wd->tx_hash, 0, 18).'…' : 'Відправлено' }}</span>
                        @else
                            <span class="text-sm text-slate-500">Розглянуто</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Виплат немає.</td></tr>
            @endforelse
        </x-table>
        <div class="mt-4">{{ $withdrawals->links() }}</div>
    </x-card>
</div>
@endsection
