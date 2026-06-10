@extends('layouts.app')
@section('title', 'CMS-сторінки')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-white">Сторінки</h1>
            <p class="mt-1 text-slate-400">Управління статичними сторінками (умови, конфіденційність, FAQ тощо).</p>
        </div>
        <x-button href="{{ route('admin.pages.create') }}" icon="plus">Нова сторінка</x-button>
    </div>
    <x-card>
        <x-table :headers="['Заголовок', 'Slug', 'Статус', 'Оновлено', 'Дії']">
            @forelse($pages as $page)
            <tr class="hover:bg-slate-900/60">
                <td class="px-4 py-3 font-medium text-white">{{ $page->title }}</td>
                <td class="px-4 py-3 font-mono text-xs"><a href="{{ route('pages.show', $page->slug) }}" target="_blank" class="text-blue-600 hover:underline">/{{ $page->slug }}</a></td>
                <td class="px-4 py-3">
                    <span class="text-xs font-semibold {{ $page->is_published ? 'text-teal-300' : 'text-amber-300' }}">
                        {{ $page->is_published ? 'Опубліковано' : 'Чернетка' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ $page->updated_at->diffForHumans() }}</td>
                <td class="px-4 py-3 flex items-center gap-3">
                    <a href="{{ route('admin.pages.edit', $page) }}" class="text-sm text-teal-300 hover:text-white">Редагувати</a>
                    <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" onsubmit="return confirm('Видалити сторінку?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-rose-400 hover:text-white">Видалити</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Сторінок ще немає.</td></tr>
            @endforelse
        </x-table>
        <div class="mt-4">{{ $pages->links() }}</div>
    </x-card>
</div>
@endsection
