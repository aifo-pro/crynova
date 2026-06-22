@extends('layouts.app')
@section('title', 'Нова стаття')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.blog.index') }}" class="text-slate-400 hover:text-slate-900"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-slate-950">Нова стаття</h1>
    </div>

    <form method="POST" action="{{ route('admin.blog.store') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-[1fr_0.4fr]" x-data="{ lang: 'uk' }">
        @csrf

        <div class="space-y-5">
            <x-card>
                <div class="mb-4 inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                    @foreach(['uk'=>'UA','en'=>'EN','pl'=>'PL','ru'=>'RU'] as $code=>$lbl)
                        <button type="button" @click="lang='{{ $code }}'" :class="lang==='{{ $code }}' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500'" class="rounded-lg px-4 py-1.5 text-sm font-bold transition">{{ $lbl }}</button>
                    @endforeach
                </div>

                <div x-show="lang==='uk'" class="space-y-4">
                    <div><label class="fin-label">Заголовок (UA)</label><input name="title" type="text" class="fin-input @error('title') border-rose-500 @enderror" value="{{ old('title') }}" required placeholder="Як почати приймати криптоплатежі">@error('title')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror</div>
                    <div><label class="fin-label">Анонс (UA)</label><textarea name="excerpt" rows="2" class="fin-input" placeholder="Короткий опис…">{{ old('excerpt') }}</textarea></div>
                    <div><label class="fin-label">Текст (UA)</label>@include('admin.blog._editor', ['content' => ''])@error('body')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror</div>
                </div>
                <div x-show="lang==='en'" x-cloak class="space-y-4">
                    <div><label class="fin-label">Title (EN)</label><input name="title_en" type="text" class="fin-input" value="{{ old('title_en') }}"></div>
                    <div><label class="fin-label">Excerpt (EN)</label><textarea name="excerpt_en" rows="2" class="fin-input">{{ old('excerpt_en') }}</textarea></div>
                    <div><label class="fin-label">Body (EN)</label>@include('admin.blog._editor', ['content' => '', 'name' => 'body_en', 'placeholder' => 'Write the article…'])</div>
                </div>
                <div x-show="lang==='pl'" x-cloak class="space-y-4">
                    <div><label class="fin-label">Tytuł (PL)</label><input name="title_pl" type="text" class="fin-input" value="{{ old('title_pl') }}"></div>
                    <div><label class="fin-label">Zajawka (PL)</label><textarea name="excerpt_pl" rows="2" class="fin-input">{{ old('excerpt_pl') }}</textarea></div>
                    <div><label class="fin-label">Treść (PL)</label>@include('admin.blog._editor', ['content' => '', 'name' => 'body_pl', 'placeholder' => 'Napisz artykuł…'])</div>
                </div>
                <div x-show="lang==='ru'" x-cloak class="space-y-4">
                    <div><label class="fin-label">Заголовок (RU)</label><input name="title_ru" type="text" class="fin-input" value="{{ old('title_ru') }}"></div>
                    <div><label class="fin-label">Анонс (RU)</label><textarea name="excerpt_ru" rows="2" class="fin-input">{{ old('excerpt_ru') }}</textarea></div>
                    <div><label class="fin-label">Текст (RU)</label>@include('admin.blog._editor', ['content' => '', 'name' => 'body_ru', 'placeholder' => 'Напишите статью…'])</div>
                </div>
                <p class="mt-3 text-xs text-slate-400">EN/PL/RU необов’язкові — якщо порожні, показується українська версія.</p>
            </x-card>

            {{-- SEO --}}
            <x-card title="SEO та мета-теги">
                <p class="mb-4 text-xs text-slate-500">Meta Title оптимально 50–60 символів, Meta Description — 120–160. Якщо залишити порожнім, використається заголовок та анонс статті.</p>

                <div x-show="lang==='uk'" class="space-y-4">
                    <x-blog-seo-field name="meta_title" label="Meta Title (UA)" :max="60" :value="old('meta_title')" placeholder="До 60 символів — показується у вкладці браузера та Google" />
                    <x-blog-seo-field name="meta_description" type="textarea" label="Meta Description (UA)" :max="160" :value="old('meta_description')" placeholder="Короткий опис для пошукової видачі (до 160 символів)" />
                </div>
                <div x-show="lang==='en'" x-cloak class="space-y-4">
                    <x-blog-seo-field name="meta_title_en" label="Meta Title (EN)" :max="60" :value="old('meta_title_en')" />
                    <x-blog-seo-field name="meta_description_en" type="textarea" label="Meta Description (EN)" :max="160" :value="old('meta_description_en')" />
                </div>
                <div x-show="lang==='pl'" x-cloak class="space-y-4">
                    <x-blog-seo-field name="meta_title_pl" label="Meta Title (PL)" :max="60" :value="old('meta_title_pl')" />
                    <x-blog-seo-field name="meta_description_pl" type="textarea" label="Meta Description (PL)" :max="160" :value="old('meta_description_pl')" />
                </div>
                <div x-show="lang==='ru'" x-cloak class="space-y-4">
                    <x-blog-seo-field name="meta_title_ru" label="Meta Title (RU)" :max="60" :value="old('meta_title_ru')" />
                    <x-blog-seo-field name="meta_description_ru" type="textarea" label="Meta Description (RU)" :max="160" :value="old('meta_description_ru')" />
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
                        <label class="fin-label">Slug <span class="text-slate-500">(URL, необовʼязково)</span></label>
                        <input name="slug" type="text" class="fin-input @error('slug') border-rose-500 @enderror" value="{{ old('slug') }}" placeholder="auto з заголовка">
                        @error('slug')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="fin-label">Теги <span class="text-slate-500">(через кому)</span></label>
                        <input name="tags" type="text" class="fin-input" value="{{ old('tags') }}" placeholder="крипто, платежі, гайд">
                    </div>

                    <x-blog-cover :value="old('cover_image')" />

                    <x-button type="submit" icon="save" class="w-full">Зберегти статтю</x-button>
                </div>
            </x-card>
        </div>
    </form>
</div>
@endsection
