@extends('layouts.app')
@section('title', 'Refunds')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-white">Повернення</h1>
        <p class="mt-1 text-slate-400">Перегляд і обробка запитів мерчантів на повернення.</p>
    </div>
    <x-card>
        <form method="GET" class="flex flex-wrap gap-3">
            <input name="search" value="{{ request('search') }}" class="fin-input flex-1 min-w-48" placeholder="Пошук мерчанта…">
            <select name="status" class="fin-input w-40">
                <option value="">Усі статуси</option>
                @foreach(['pending'=>'Очікує','approved'=>'Схвалено','processing'=>'Обробляється','completed'=>'Завершено','rejected'=>'Відхилено','failed'=>'Помилка'] as $s=>$lbl)
                    <option value="{{ $s }}" @selected(request('status') == $s)>{{ $lbl }}</option>
                @endforeach
            </select>
            <x-button type="submit" variant="secondary">Фільтр</x-button>
        </form>
    </x-card>

    <x-card>
        <x-table :headers="['UUID', 'Мерчант', 'Валюта', 'Сума', 'Тип', 'Статус', 'Запит', 'Дії']">
            @forelse($refunds as $refund)
            @php
                $sc = [
                    'pending'    => 'text-amber-300',
                    'approved'   => 'text-blue-300',
                    'processing' => 'text-violet-300',
                    'completed'  => 'text-teal-300',
                    'rejected'   => 'text-rose-400',
                    'failed'     => 'text-rose-400',
                ];
            @endphp
            <tr class="hover:bg-slate-900/60">
                <td class="px-4 py-3 font-mono text-xs text-teal-200">{{ substr($refund->uuid, 0, 8) }}…</td>
                <td class="px-4 py-3 text-sm text-slate-300">{{ $refund->merchant->name }}</td>
                <td class="px-4 py-3 font-semibold text-white">{{ $refund->currency->code }}</td>
                <td class="px-4 py-3 font-mono text-sm">{{ $refund->amount }}</td>
                <td class="px-4 py-3 text-xs">{{ ucfirst($refund->type) }}</td>
                <td class="px-4 py-3 text-xs font-semibold {{ $sc[$refund->status] ?? 'text-slate-400' }}">{{ ucfirst($refund->status) }}</td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ $refund->created_at->diffForHumans() }}</td>
                <td class="px-4 py-3">
                    @if(! $refund->isFinal())
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('admin.refunds.approve', $refund) }}">
                            @csrf
                            <button type="submit" class="text-xs text-teal-300 hover:text-white">Схвалити</button>
                        </form>
                        <form method="POST" action="{{ route('admin.refunds.reject', $refund) }}">
                            @csrf
                            <button type="submit" class="text-xs text-rose-400 hover:text-white">Відхилити</button>
                        </form>
                    </div>
                    @else
                        <span class="text-xs text-slate-600">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">Запитів на повернення немає.</td></tr>
            @endforelse
        </x-table>
        <div class="mt-4">{{ $refunds->links() }}</div>
    </x-card>
</div>
@endsection
