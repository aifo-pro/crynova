@extends('layouts.app')
@section('title', 'Редагування статті')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.blog.index') }}" class="text-slate-400 hover:text-slate-900"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-slate-950">Редагування статті</h1>
    </div>
    <form method="POST" action="{{ route('admin.blog.update', $post) }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-[1fr_0.4fr]" x-data="{ lang: 'uk' }">
        @csrf @method('PATCH')

        <div class="space-y-5">
            <x-card>
                <div class="mb-4 inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                    @foreach(['uk'=>'UA','en'=>'EN','pl'=>'PL'] as $code=>$lbl)
                        <button type="button" @click="lang='{{ $code }}'" :class="lang==='{{ $code }}' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500'" class="rounded-lg px-4 py-1.5 text-sm font-bold transition">{{ $lbl }}</button>
                    @endforeach
                </div>

                <div x-show="lang==='uk'" class="space-y-4">
                    <div><label class="fin-label">Заголовок (UA)</label><input name="title" type="text" class="fin-input @error('title') border-rose-500 @enderror" value="{{ old('title', $post->title) }}" required>@error('title')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror</div>
                    <div><label class="fin-label">Анонс (UA)</label><textarea name="excerpt" rows="2" class="fin-input">{{ old('excerpt', $post->excerpt) }}</textarea></div>
                    <div><label class="fin-label">Текст (UA)</label>@include('admin.blog._editor', ['content' => $post->body])@error('body')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror</div>
                </div>
                <div x-show="lang==='en'" x-cloak class="space-y-4">
                    <div><label class="fin-label">Title (EN)</label><input name="title_en" type="text" class="fin-input" value="{{ old('title_en', $post->title_en) }}"></div>
                    <div><label class="fin-label">Excerpt (EN)</label><textarea name="excerpt_en" rows="2" class="fin-input">{{ old('excerpt_en', $post->excerpt_en) }}</textarea></div>
                    <div><label class="fin-label">Body (EN)</label>@include('admin.blog._editor', ['content' => $post->body_en, 'name' => 'body_en', 'placeholder' => 'Write the article…'])</div>
                </div>
                <div x-show="lang==='pl'" x-cloak class="space-y-4">
                    <div><label class="fin-label">Tytuł (PL)</label><input name="title_pl" type="text" class="fin-input" value="{{ old('title_pl', $post->title_pl) }}"></div>
                    <div><label class="fin-label">Zajawka (PL)</label><textarea name="excerpt_pl" rows="2" class="fin-input">{{ old('excerpt_pl', $post->excerpt_pl) }}</textarea></div>
                    <div><label class="fin-label">Treść (PL)</label>@include('admin.blog._editor', ['content' => $post->body_pl, 'name' => 'body_pl', 'placeholder' => 'Napisz artykuł…'])</div>
                </div>
                <p class="mt-3 text-xs text-slate-400">EN/PL необов’язкові — якщо порожні, показується українська версія.</p>
            </x-card>

            {{-- SEO --}}
            <x-card title="SEO та мета-теги">
                <p class="mb-4 text-xs text-slate-500">Meta Title оптимально 50–60 символів, Meta Description — 120–160. Якщо залишити порожнім, використається заголовок та анонс статті.</p>

                <div x-show="lang==='uk'" class="space-y-4">
                    <x-blog-seo-field name="meta_title" label="Meta Title (UA)" :max="60" :value="old('meta_title', $post->meta_title)" placeholder="До 60 символів — показується у вкладці браузера та Google" />
                    <x-blog-seo-field name="meta_description" type="textarea" label="Meta Description (UA)" :max="160" :value="old('meta_description', $post->meta_description)" placeholder="Короткий опис для пошукової видачі (до 160 символів)" />
                </div>
                <div x-show="lang==='en'" x-cloak class="space-y-4">
                    <x-blog-seo-field name="meta_title_en" label="Meta Title (EN)" :max="60" :value="old('meta_title_en', $post->meta_title_en)" />
                    <x-blog-seo-field name="meta_description_en" type="textarea" label="Meta Description (EN)" :max="160" :value="old('meta_description_en', $post->meta_description_en)" />
                </div>
                <div x-show="lang==='pl'" x-cloak class="space-y-4">
                    <x-blog-seo-field name="meta_title_pl" label="Meta Title (PL)" :max="60" :value="old('meta_title_pl', $post->meta_title_pl)" />
                    <x-blog-seo-field name="meta_description_pl" type="textarea" label="Meta Description (PL)" :max="160" :value="old('meta_description_pl', $post->meta_description_pl)" />
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
                        <label class="fin-label">Slug <span class="text-slate-500">(URL)</span></label>
                        <input name="slug" type="text" class="fin-input @error('slug') border-rose-500 @enderror" value="{{ old('slug', $post->slug) }}">
                        @error('slug')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="fin-label">Теги <span class="text-slate-500">(через кому)</span></label>
                        <input name="tags" type="text" class="fin-input"
                               value="{{ old('tags', is_array($post->tags) ? implode(', ', $post->tags) : '') }}">
                    </div>

                    <x-blog-cover :value="$post->cover_image" />

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
