@extends('layouts.app')
@section('title', 'Редагування сторінки')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.pages.index') }}" class="text-slate-400 hover:text-slate-900"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-slate-950">Редагування сторінки</h1>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="grid gap-6 xl:grid-cols-[1fr_0.4fr]" x-data="{ lang: 'uk' }">
        @csrf @method('PATCH')

        <x-card>
            {{-- Language tabs --}}
            <div class="mb-4 inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                @foreach(['uk'=>'UA','en'=>'EN','pl'=>'PL'] as $code=>$lbl)
                    <button type="button" @click="lang='{{ $code }}'" :class="lang==='{{ $code }}' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500'" class="rounded-lg px-4 py-1.5 text-sm font-bold transition">{{ $lbl }}</button>
                @endforeach
            </div>

            {{-- UK (default) --}}
            <div x-show="lang==='uk'" class="space-y-4">
                <div><label class="fin-label">Заголовок (UA)</label><input name="title" type="text" class="fin-input" value="{{ old('title', $page->title) }}" required></div>
                <div><label class="fin-label">Текст (UA) <span class="text-slate-400">(HTML)</span></label><textarea name="body" rows="16" class="fin-input font-mono text-sm" required>{{ old('body', $page->body) }}</textarea></div>
                <div><label class="fin-label">Meta title (UA)</label><input name="meta_title" type="text" class="fin-input" value="{{ old('meta_title', $page->meta_title) }}"></div>
                <div><label class="fin-label">Meta description (UA)</label><textarea name="meta_description" rows="2" class="fin-input">{{ old('meta_description', $page->meta_description) }}</textarea></div>
            </div>
            {{-- EN --}}
            <div x-show="lang==='en'" x-cloak class="space-y-4">
                <div><label class="fin-label">Title (EN)</label><input name="title_en" type="text" class="fin-input" value="{{ old('title_en', $page->title_en) }}"></div>
                <div><label class="fin-label">Body (EN) <span class="text-slate-400">(HTML)</span></label><textarea name="body_en" rows="16" class="fin-input font-mono text-sm">{{ old('body_en', $page->body_en) }}</textarea></div>
                <div><label class="fin-label">Meta title (EN)</label><input name="meta_title_en" type="text" class="fin-input" value="{{ old('meta_title_en', $page->meta_title_en) }}"></div>
                <div><label class="fin-label">Meta description (EN)</label><textarea name="meta_description_en" rows="2" class="fin-input">{{ old('meta_description_en', $page->meta_description_en) }}</textarea></div>
            </div>
            {{-- PL --}}
            <div x-show="lang==='pl'" x-cloak class="space-y-4">
                <div><label class="fin-label">Tytuł (PL)</label><input name="title_pl" type="text" class="fin-input" value="{{ old('title_pl', $page->title_pl) }}"></div>
                <div><label class="fin-label">Treść (PL) <span class="text-slate-400">(HTML)</span></label><textarea name="body_pl" rows="16" class="fin-input font-mono text-sm">{{ old('body_pl', $page->body_pl) }}</textarea></div>
                <div><label class="fin-label">Meta title (PL)</label><input name="meta_title_pl" type="text" class="fin-input" value="{{ old('meta_title_pl', $page->meta_title_pl) }}"></div>
                <div><label class="fin-label">Meta description (PL)</label><textarea name="meta_description_pl" rows="2" class="fin-input">{{ old('meta_description_pl', $page->meta_description_pl) }}</textarea></div>
            </div>
            <p class="mt-3 text-xs text-slate-400">EN/PL необов’язкові — якщо порожні, показується українська версія.</p>
        </x-card>

        <div class="space-y-5">
            <x-card title="Публікація">
                <div class="space-y-4">
                    <div>
                        <label class="fin-label">Slug <span class="text-slate-400">(адреса: /{{ $page->slug }})</span></label>
                        <input name="slug" type="text" class="fin-input" value="{{ old('slug', $page->slug) }}">
                    </div>
                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published))
                               class="rounded border-slate-300 text-blue-600">
                        <span class="text-sm text-slate-700">Опубліковано</span>
                    </label>
                    <x-button type="submit" icon="save" class="w-full">Зберегти сторінку</x-button>
                </div>
            </x-card>

            <x-card>
                {{-- Delete button targets a separate form (outside) — never nest forms. --}}
                <button type="submit" form="delete-page-form"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-100">
                    <x-icon name="alert-triangle" class="h-4 w-4" /> Видалити сторінку
                </button>
            </x-card>
        </div>
    </form>

    {{-- Standalone delete form (not nested inside the edit form). --}}
    <form id="delete-page-form" method="POST" action="{{ route('admin.pages.destroy', $page) }}"
          onsubmit="return confirm('Видалити сторінку остаточно?')" class="hidden">
        @csrf @method('DELETE')
    </form>
</div>
@endsection
