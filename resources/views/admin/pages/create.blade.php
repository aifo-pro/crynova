@extends('layouts.app')
@section('title', 'Нова сторінка')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.pages.index') }}" class="text-slate-400 hover:text-white"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-white">Нова сторінка</h1>
    </div>

    <form method="POST" action="{{ route('admin.pages.store') }}" class="grid gap-6 xl:grid-cols-[1fr_0.4fr]">
        @csrf

        <x-card>
            <div class="space-y-4">
                <div>
                    <label class="fin-label">Заголовок</label>
                    <input name="title" type="text" class="fin-input @error('title') border-rose-500 @enderror"
                           value="{{ old('title') }}" required placeholder="Умови користування">
                    @error('title')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="fin-label">Slug <span class="text-slate-500">(генерується автоматично, якщо порожній)</span></label>
                    <input name="slug" type="text" class="fin-input @error('slug') border-rose-500 @enderror"
                           value="{{ old('slug') }}" placeholder="terms-of-service">
                    @error('slug')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="fin-label">Текст <span class="text-slate-500">(HTML)</span></label>
                    <textarea name="body" rows="18" class="fin-input font-mono text-sm @error('body') border-rose-500 @enderror"
                              required>{{ old('body') }}</textarea>
                    @error('body')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-card>

        <x-card title="SEO та публікація">
            <div class="space-y-4">
                <div>
                    <label class="fin-label">Meta title</label>
                    <input name="meta_title" type="text" class="fin-input" value="{{ old('meta_title') }}">
                </div>
                <div>
                    <label class="fin-label">Meta description</label>
                    <textarea name="meta_description" rows="3" class="fin-input">{{ old('meta_description') }}</textarea>
                </div>
                <label class="flex cursor-pointer items-center gap-3">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published'))
                           class="rounded border-slate-700 text-teal-400">
                    <span class="text-sm text-slate-300">Опублікувати одразу</span>
                </label>
                <x-button type="submit" icon="save" class="w-full">Створити сторінку</x-button>
            </div>
        </x-card>
    </form>
</div>
@endsection
