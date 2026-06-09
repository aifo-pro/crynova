@extends('layouts.app')
@section('title', 'Редагування сторінки')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.pages.index') }}" class="text-slate-400 hover:text-white"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-white">Редагування сторінки</h1>
    </div>
    <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="grid gap-6 xl:grid-cols-[1fr_0.4fr]">
        @csrf @method('PATCH')

        <x-card>
            <div class="space-y-4">
                <div>
                    <label class="fin-label">Заголовок</label>
                    <input name="title" type="text" class="fin-input" value="{{ old('title', $page->title) }}" required>
                </div>
                <div>
                    <label class="fin-label">Slug</label>
                    <input name="slug" type="text" class="fin-input" value="{{ old('slug', $page->slug) }}">
                </div>
                <div>
                    <label class="fin-label">Текст</label>
                    <textarea name="body" rows="18" class="fin-input font-mono text-sm" required>{{ old('body', $page->body) }}</textarea>
                </div>
            </div>
        </x-card>

        <div class="space-y-5">
            <x-card title="SEO та публікація">
                <div class="space-y-4">
                    <div>
                        <label class="fin-label">Meta title</label>
                        <input name="meta_title" type="text" class="fin-input" value="{{ old('meta_title', $page->meta_title) }}">
                    </div>
                    <div>
                        <label class="fin-label">Meta description</label>
                        <textarea name="meta_description" rows="3" class="fin-input">{{ old('meta_description', $page->meta_description) }}</textarea>
                    </div>
                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published))
                               class="rounded border-slate-700 text-teal-400">
                        <span class="text-sm text-slate-300">Опубліковано</span>
                    </label>
                    <x-button type="submit" icon="save" class="w-full">Зберегти сторінку</x-button>
                </div>
            </x-card>

            <x-card>
                <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" onsubmit="return confirm('Видалити сторінку?')">
                    @csrf @method('DELETE')
                    <x-button type="submit" variant="danger" icon="trash" class="w-full">Видалити сторінку</x-button>
                </form>
            </x-card>
        </div>
    </form>
</div>
@endsection
