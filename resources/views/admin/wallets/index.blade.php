@extends('layouts.app')
@section('title', 'Гаманці')

@section('content')
<div class="space-y-6">
    <div>
        <x-badge variant="blue">Адмін-панель</x-badge>
        <h1 class="mt-3 text-3xl font-semibold text-white">Гаманці</h1>
        <p class="mt-1 text-slate-400">Реєстр адрес для депозитів за мережею і типом. Приватні ключі ніколи не зберігаються.</p>
    </div>

    <x-card>
        <form method="GET" class="grid gap-3 md:grid-cols-3">
            <select name="currency" class="fin-input">
                <option value="">Валюта: усі</option>
                @foreach(\App\Models\Currency::orderBy('code')->get() as $c)
                    <option value="{{ $c->code }}" @selected(request('currency') == $c->code)>{{ $c->code }}</option>
                @endforeach
            </select>
            <select name="type" class="fin-input">
                <option value="">Тип: усі</option>
                <option value="hot" @selected(request('type')=='hot')>Hot</option>
                <option value="cold" @selected(request('type')=='cold')>Cold</option>
                <option value="external" @selected(request('type')=='external')>External</option>
            </select>
            <x-button type="submit" variant="secondary">Фільтр</x-button>
        </form>
    </x-card>

    <x-card>
        <x-table :headers="['Адреса', 'Валюта / Мережа', 'Тип', 'Баланс', 'Рахунок', 'Статус', 'Перевірено']">
            @forelse($wallets as $wallet)
            <tr class="hover:bg-slate-900/60">
                <td class="px-4 py-3 font-mono text-xs text-teal-200">
                    {{ substr($wallet->address, 0, 18) }}…
                </td>
                <td class="px-4 py-3">
                    <p class="font-semibold text-white">{{ $wallet->currency->code }}</p>
                    <p class="text-xs text-slate-500">{{ $wallet->currency->network }}</p>
                </td>
                <td class="px-4 py-3">
                    <x-badge :variant="$wallet->type === 'hot' ? 'teal' : ($wallet->type === 'cold' ? 'blue' : 'slate')">
                        {{ $wallet->type }}
                    </x-badge>
                </td>
                <td class="px-4 py-3 font-mono text-sm">{{ $wallet->balance }}</td>
                <td class="px-4 py-3 font-mono text-xs text-slate-400">
                    {{ $wallet->invoice ? substr($wallet->invoice->uuid, 0, 8) . '…' : '—' }}
                </td>
                <td class="px-4 py-3">
                    @if($wallet->is_used)
                        <x-badge variant="yellow">Використовується</x-badge>
                    @else
                        <x-badge variant="green">Вільний</x-badge>
                    @endif
                </td>
                <td class="px-4 py-3 text-xs text-slate-500">
                    {{ $wallet->last_checked_at?->diffForHumans() ?? 'Ніколи' }}
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">Гаманців не знайдено.</td></tr>
            @endforelse
        </x-table>
        <div class="mt-4">{{ $wallets->links() }}</div>
    </x-card>
</div>
@endsection
