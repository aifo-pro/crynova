@extends('layouts.app')
@section('title', 'Нова сторінка')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.pages.index') }}" class="text-slate-400 hover:text-slate-900"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-slate-950">Нова сторінка</h1>
    </div>

    <form method="POST" action="{{ route('admin.pages.store') }}" class="grid gap-6 xl:grid-cols-[1fr_0.4fr]" x-data="{ lang: 'uk' }">
        @csrf

        <x-card>
            <div class="mb-4 inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                @foreach(['uk'=>'UA','en'=>'EN','pl'=>'PL'] as $code=>$lbl)
                    <button type="button" @click="lang='{{ $code }}'" :class="lang==='{{ $code }}' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500'" class="rounded-lg px-4 py-1.5 text-sm font-bold transition">{{ $lbl }}</button>
                @endforeach
            </div>

            <div x-show="lang==='uk'" class="space-y-4">
                <div><label class="fin-label">Заголовок (UA)</label><input name="title" type="text" class="fin-input @error('title') border-rose-500 @enderror" value="{{ old('title') }}" required placeholder="Умови користування">@error('title')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror</div>
                <div><label class="fin-label">Текст (UA) <span class="text-slate-400">(HTML)</span></label><textarea name="body" rows="16" class="fin-input font-mono text-sm @error('body') border-rose-500 @enderror" required>{{ old('body') }}</textarea>@error('body')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror</div>
                <div><label class="fin-label">Meta title (UA)</label><input name="meta_title" type="text" class="fin-input" value="{{ old('meta_title') }}"></div>
                <div><label class="fin-label">Meta description (UA)</label><textarea name="meta_description" rows="2" class="fin-input">{{ old('meta_description') }}</textarea></div>
            </div>
            <div x-show="lang==='en'" x-cloak class="space-y-4">
                <div><label class="fin-label">Title (EN)</label><input name="title_en" type="text" class="fin-input" value="{{ old('title_en') }}"></div>
                <div><label class="fin-label">Body (EN) <span class="text-slate-400">(HTML)</span></label><textarea name="body_en" rows="16" class="fin-input font-mono text-sm">{{ old('body_en') }}</textarea></div>
                <div><label class="fin-label">Meta title (EN)</label><input name="meta_title_en" type="text" class="fin-input" value="{{ old('meta_title_en') }}"></div>
                <div><label class="fin-label">Meta description (EN)</label><textarea name="meta_description_en" rows="2" class="fin-input">{{ old('meta_description_en') }}</textarea></div>
            </div>
            <div x-show="lang==='pl'" x-cloak class="space-y-4">
                <div><label class="fin-label">Tytuł (PL)</label><input name="title_pl" type="text" class="fin-input" value="{{ old('title_pl') }}"></div>
                <div><label class="fin-label">Treść (PL) <span class="text-slate-400">(HTML)</span></label><textarea name="body_pl" rows="16" class="fin-input font-mono text-sm">{{ old('body_pl') }}</textarea></div>
                <div><label class="fin-label">Meta title (PL)</label><input name="meta_title_pl" type="text" class="fin-input" value="{{ old('meta_title_pl') }}"></div>
                <div><label class="fin-label">Meta description (PL)</label><textarea name="meta_description_pl" rows="2" class="fin-input">{{ old('meta_description_pl') }}</textarea></div>
            </div>
            <p class="mt-3 text-xs text-slate-400">EN/PL необов’язкові — якщо порожні, показується українська версія.</p>
        </x-card>

        <x-card title="Публікація">
            <div class="space-y-4">
                <div>
                    <label class="fin-label">Slug <span class="text-slate-400">(автоматично, якщо порожній)</span></label>
                    <input name="slug" type="text" class="fin-input @error('slug') border-rose-500 @enderror" value="{{ old('slug') }}" placeholder="terms-of-service">
                    @error('slug')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
                <label class="flex cursor-pointer items-center gap-3">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published')) class="rounded border-slate-300 text-blue-600">
                    <span class="text-sm text-slate-700">Опублікувати одразу</span>
                </label>
                <x-button type="submit" icon="save" class="w-full">Створити сторінку</x-button>
            </div>
        </x-card>
    </form>
</div>
@endsection
