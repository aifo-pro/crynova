@extends('layouts.app')
@section('title', 'Модулі інтеграції')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-950">Модулі інтеграції</h1>
            <p class="mt-1 text-slate-500">Каталог CMS-модулів для завантаження в кабінеті користувача. Дані мерчанта тут не показуються.</p>
        </div>
        <x-button href="{{ route('admin.modules.create') }}" icon="plus">Новий модуль</x-button>
    </div>
    <x-card>
        <x-table :headers="['Назва', 'Slug', 'Файл', 'Статус', 'Порядок', 'Дії']">
            @forelse($modules as $module)
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-3 font-medium text-slate-900">{{ $module->name }}</td>
                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $module->slug }}</td>
                <td class="px-4 py-3 text-xs">
                    @if($module->file_path)
                        <span class="text-emerald-600">файл</span>
                    @elseif($module->external_url)
                        <span class="text-sky-600">посилання</span>
                    @else
                        <span class="text-rose-500">немає</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs font-semibold {{ $module->is_active ? 'text-emerald-600' : 'text-amber-600' }}">
                        {{ $module->is_active ? 'Доступний' : 'Прихований' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ $module->sort }}</td>
                <td class="px-4 py-3 flex items-center gap-3">
                    <a href="{{ route('admin.modules.edit', $module) }}" class="text-sm font-semibold text-blue-600 hover:underline">Редагувати</a>
                    <form method="POST" action="{{ route('admin.modules.destroy', $module) }}" onsubmit="return confirm('Видалити модуль?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm font-semibold text-rose-500 hover:underline">Видалити</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Модулів ще немає. Додайте перший.</td></tr>
            @endforelse
        </x-table>
    </x-card>
</div>
@endsection
