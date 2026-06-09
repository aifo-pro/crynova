@extends('layouts.app')
@section('title', 'Виплати')

@section('content')
<div class="space-y-6">
    <div><h1 class="text-3xl font-semibold text-white">Виплати</h1><p class="mt-1 text-slate-400">Схвалення або відхилення запитів мерчантів на виплати.</p></div>
    <x-card>
        <x-table :headers="['Мерчант', 'Валюта', 'Сума', 'Призначення', 'Статус', 'Дії']">
            @forelse($withdrawals as $wd)
                <tr class="hover:bg-slate-900/60 align-top">
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
                        @else
                            <span class="text-sm text-slate-500">Розглянуто</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Виплат немає.</td></tr>
            @endforelse
        </x-table>
        <div class="mt-4">{{ $withdrawals->links() }}</div>
    </x-card>
</div>
@endsection
