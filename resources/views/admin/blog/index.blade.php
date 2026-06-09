@extends('layouts.app')
@section('title', 'Блог')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-white">Блог</h1>
            <p class="mt-1 text-slate-400">Управління статтями та анонсами.</p>
        </div>
        <x-button href="{{ route('admin.blog.create') }}" icon="plus">Нова стаття</x-button>
    </div>
    <x-card>
        <form method="GET" class="flex flex-wrap gap-3">
            <input name="search" value="{{ request('search') }}" class="fin-input flex-1 min-w-48" placeholder="Пошук статей…">
            <select name="status" class="fin-input w-40">
                <option value="">Усі статуси</option>
                @foreach(['draft'=>'Чернетка','published'=>'Опубліковано','archived'=>'Архів'] as $s=>$lbl)
                    <option value="{{ $s }}" @selected(request('status') == $s)>{{ $lbl }}</option>
                @endforeach
            </select>
            <x-button type="submit" variant="secondary">Фільтр</x-button>
        </form>
    </x-card>

    <x-card>
        <x-table :headers="['Заголовок', 'Автор', 'Статус', 'Опубліковано', 'Дії']">
            @forelse($posts as $post)
            <tr class="hover:bg-slate-900/60">
                <td class="px-4 py-3">
                    <p class="font-medium text-white">{{ $post->title }}</p>
                    <p class="text-xs text-slate-500">/blog/{{ $post->slug }}</p>
                </td>
                <td class="px-4 py-3 text-sm text-slate-400">{{ $post->author?->name ?? '—' }}</td>
                <td class="px-4 py-3">
                    @php $colors = ['draft'=>'text-amber-300','published'=>'text-teal-300','archived'=>'text-slate-400']; @endphp
                    <span class="text-xs font-semibold {{ $colors[$post->status] ?? '' }}">{{ ucfirst($post->status) }}</span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ $post->published_at?->format('d M Y') ?? '—' }}</td>
                <td class="px-4 py-3 flex items-center gap-3">
                    <a href="{{ route('admin.blog.edit', $post) }}" class="text-sm text-teal-300 hover:text-white">Редагувати</a>
                    <form method="POST" action="{{ route('admin.blog.destroy', $post) }}" onsubmit="return confirm('Видалити статтю?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-rose-400 hover:text-white">Видалити</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Статей ще немає.</td></tr>
            @endforelse
        </x-table>
        <div class="mt-4">{{ $posts->links() }}</div>
    </x-card>
</div>
@endsection
