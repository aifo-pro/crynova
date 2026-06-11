@extends('layouts.app')
@section('title', 'Редагування статті')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.blog.index') }}" class="text-slate-400 hover:text-white"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-white">Редагування статті</h1>
    </div>
    <form method="POST" action="{{ route('admin.blog.update', $post) }}" class="grid gap-6 xl:grid-cols-[1fr_0.4fr]">
        @csrf @method('PATCH')

        <div class="space-y-5">
            <x-card>
                <div class="space-y-4">
                    <div>
                        <label class="fin-label">Заголовок</label>
                        <input name="title" type="text" class="fin-input @error('title') border-rose-500 @enderror"
                               value="{{ old('title', $post->title) }}" required>
                        @error('title')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="fin-label">Анонс</label>
                        <textarea name="excerpt" rows="2" class="fin-input">{{ old('excerpt', $post->excerpt) }}</textarea>
                    </div>
                    <div>
                        <label class="fin-label">Текст</label>
                        <textarea name="body" rows="16" class="fin-input font-mono text-sm" required>{{ old('body', $post->body) }}</textarea>
                        @error('body')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                </div>
            </x-card>
        </div>

        <div class="space-y-5">
            <x-card title="Налаштування публікації">
                <div class="space-y-4">
                    <div>
                        <label class="fin-label">Статус</label>
                        <select name="status" class="fin-input">
                            @foreach(['draft'=>'Чернетка','published'=>'Опубліковано','archived'=>'Архів'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('status', $post->status) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="fin-label">Теги <span class="text-slate-500">(через кому)</span></label>
                        <input name="tags" type="text" class="fin-input"
                               value="{{ old('tags', is_array($post->tags) ? implode(', ', $post->tags) : '') }}">
                    </div>
                    <div>
                        <label class="fin-label">URL обкладинки</label>
                        <input name="cover_image" type="url" class="fin-input" value="{{ old('cover_image', $post->cover_image) }}">
                    </div>
                    <p class="text-xs text-slate-500">Slug: <code class="text-teal-300">{{ $post->slug }}</code></p>
                    <x-button type="submit" icon="save" class="w-full">Оновити статтю</x-button>
                </div>
            </x-card>

            <x-card>
                {{-- Delete targets a standalone form (outside) — never nest forms. --}}
                <button type="submit" form="delete-post-form"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-100">
                    <x-icon name="alert-triangle" class="h-4 w-4" /> Видалити статтю
                </button>
            </x-card>
        </div>
    </form>

    <form id="delete-post-form" method="POST" action="{{ route('admin.blog.destroy', $post) }}"
          onsubmit="return confirm('Видалити статтю назавжди?')" class="hidden">
        @csrf @method('DELETE')
    </form>
</div>
@endsection
