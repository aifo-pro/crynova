@extends('layouts.app')
@section('title', 'Новини')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-950">Новини</h1>
            <p class="mt-1 text-slate-500">Анонси та новини. Три останні показуються на головній.</p>
        </div>
        <x-button href="{{ route('admin.news.create') }}" icon="plus">Нова новина</x-button>
    </div>

    <form method="GET" class="flex flex-wrap gap-2">
        <input name="search" value="{{ request('search') }}" class="fin-input min-w-56 flex-1" placeholder="Пошук…">
        <select name="status" class="fin-input w-44">
            <option value="">Усі статуси</option>
            @foreach(['draft'=>'Чернетка','published'=>'Опубліковано','archived'=>'Архів'] as $s=>$lbl)
                <option value="{{ $s }}" @selected(request('status') == $s)>{{ $lbl }}</option>
            @endforeach
        </select>
        <x-button type="submit" variant="secondary">Фільтр</x-button>
    </form>

    <x-card>
        <x-table :headers="['Заголовок', 'Статус', 'Опубліковано', 'Дії']">
            @forelse($items as $item)
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-3">
                    <p class="font-medium text-slate-900">{{ $item->title }}</p>
                    <a href="{{ route('news.show', $item->slug) }}" target="_blank" class="text-xs text-blue-600 hover:underline">/news/{{ $item->slug }}</a>
                </td>
                <td class="px-4 py-3">
                    @php $colors = ['draft'=>'text-amber-600','published'=>'text-emerald-600','archived'=>'text-slate-400']; @endphp
                    <span class="text-xs font-semibold {{ $colors[$item->status] ?? '' }}">{{ ucfirst($item->status) }}</span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ optional($item->published_at)->format('d.m.Y') ?? '—' }}</td>
                <td class="px-4 py-3 flex items-center gap-3">
                    <a href="{{ route('admin.news.edit', $item) }}" class="text-sm font-semibold text-blue-600 hover:underline">Редагувати</a>
                    <form method="POST" action="{{ route('admin.news.destroy', $item) }}" onsubmit="return confirm('Видалити новину?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm font-semibold text-rose-500 hover:underline">Видалити</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">Новин ще немає.</td></tr>
            @endforelse
        </x-table>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-card>
</div>
@endsection
