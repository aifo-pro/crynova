@extends('layouts.app')
@section('title', 'Нова стаття')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.blog.index') }}" class="text-slate-400 hover:text-white"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-white">Нова стаття</h1>
    </div>

    <form method="POST" action="{{ route('admin.blog.store') }}" class="grid gap-6 xl:grid-cols-[1fr_0.4fr]">
        @csrf

        <div class="space-y-5">
            <x-card>
                <div class="space-y-4">
                    <div>
                        <label class="fin-label">Заголовок</label>
                        <input name="title" type="text" class="fin-input @error('title') border-rose-500 @enderror"
                               value="{{ old('title') }}" required placeholder="Як почати приймати криптоплатежі">
                        @error('title')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="fin-label">Анонс <span class="text-slate-500">(необовʼязково, показується у списку)</span></label>
                        <textarea name="excerpt" rows="2" class="fin-input @error('excerpt') border-rose-500 @enderror"
                                  placeholder="Короткий опис…">{{ old('excerpt') }}</textarea>
                        @error('excerpt')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="fin-label">Текст <span class="text-slate-500">(HTML або markdown)</span></label>
                        <textarea name="body" rows="16" class="fin-input font-mono text-sm @error('body') border-rose-500 @enderror"
                                  placeholder="<h2>Introduction</h2>&#10;<p>...</p>" required>{{ old('body') }}</textarea>
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
                                <option value="{{ $val }}" @selected(old('status','draft') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="fin-label">Теги <span class="text-slate-500">(через кому)</span></label>
                        <input name="tags" type="text" class="fin-input" value="{{ old('tags') }}" placeholder="крипто, платежі, гайд">
                    </div>
                    <div>
                        <label class="fin-label">URL обкладинки <span class="text-slate-500">(необовʼязково)</span></label>
                        <input name="cover_image" type="url" class="fin-input @error('cover_image') border-rose-500 @enderror"
                               value="{{ old('cover_image') }}" placeholder="https://...">
                        @error('cover_image')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                    <x-button type="submit" icon="save" class="w-full">Зберегти статтю</x-button>
                </div>
            </x-card>
        </div>
    </form>
</div>
@endsection
