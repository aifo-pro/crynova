@extends('layouts.app')
@section('title', 'Currencies')

@section('content')
<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold text-slate-950">Валюти</h1>
            <p class="mt-1 text-slate-500">Налаштування підтримуваних монет і мереж.</p>
        </div>
        <x-button href="{{ route('admin.currencies.create') }}" icon="plus">Додати валюту</x-button>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3.5">Валюта</th>
                        <th class="px-4 py-3.5">Мережа</th>
                        <th class="px-4 py-3.5 text-center">Знаків</th>
                        <th class="px-4 py-3.5 text-center">Підтверджень</th>
                        <th class="px-4 py-3.5">Статус</th>
                        <th class="px-4 py-3.5 text-right">Дії</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($currencies as $currency)
                    <tr class="transition hover:bg-slate-50/60">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <x-coin-icon :code="$currency->code" class="h-9 w-9 shrink-0" />
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-950">{{ $currency->code }}</p>
                                    <p class="truncate text-xs text-slate-400">{{ $currency->name }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-slate-600">{{ strtoupper($currency->network) }}</span>
                        </td>
                        <td class="px-4 py-4 text-center font-mono text-slate-600">{{ $currency->decimals }}</td>
                        <td class="px-4 py-4 text-center font-mono text-slate-600">{{ $currency->confirmations_required }}</td>
                        <td class="px-4 py-4">
                            @if($currency->is_active)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-600"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Активна</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-400"><span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Вимкнена</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.currencies.edit', $currency) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600">
                                    <x-icon name="edit" class="h-3.5 w-3.5" /> Редагувати
                                </a>
                                <form method="POST" action="{{ route('admin.currencies.toggle', $currency) }}">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $currency->is_active ? 'border border-amber-200 bg-amber-50 text-amber-600 hover:bg-amber-100' : 'border border-emerald-200 bg-emerald-50 text-emerald-600 hover:bg-emerald-100' }}">
                                        {{ $currency->is_active ? 'Вимкнути' : 'Увімкнути' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
