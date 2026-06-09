@extends('layouts.app')
@section('title', 'Currencies')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-white">Валюти</h1>
        <p class="mt-1 text-slate-400">Налаштування підтримуваних монет і мереж.</p>
    </div>
    <x-card>
        <x-table :headers="['Код', 'Назва', 'Мережа', 'Знаків', 'Підтверджень', 'Статус', 'Дії']">
            @foreach($currencies as $currency)
            <tr class="hover:bg-slate-900/60">
                <td class="px-4 py-3 font-mono font-semibold text-teal-200">{{ $currency->code }}</td>
                <td class="px-4 py-3 text-sm text-slate-300">{{ $currency->name }}</td>
                <td class="px-4 py-3">
                    <x-badge>{{ strtoupper($currency->network) }}</x-badge>
                </td>
                <td class="px-4 py-3 text-sm text-slate-400">{{ $currency->decimals }}</td>
                <td class="px-4 py-3 text-sm text-slate-400">{{ $currency->confirmations_required }}</td>
                <td class="px-4 py-3">
                    <span class="text-xs font-semibold {{ $currency->is_active ? 'text-teal-300' : 'text-rose-400' }}">
                        {{ $currency->is_active ? 'Активна' : 'Вимкнена' }}
                    </span>
                </td>
                <td class="px-4 py-3 flex items-center gap-3">
                    <a href="{{ route('admin.currencies.edit', $currency) }}" class="text-sm text-teal-300 hover:text-white">Редагувати</a>
                    <form method="POST" action="{{ route('admin.currencies.toggle', $currency) }}">
                        @csrf
                        <button type="submit" class="text-sm {{ $currency->is_active ? 'text-amber-400' : 'text-teal-400' }} hover:text-white">
                            {{ $currency->is_active ? 'Вимкнути' : 'Увімкнути' }}
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </x-table>
    </x-card>
</div>
@endsection
