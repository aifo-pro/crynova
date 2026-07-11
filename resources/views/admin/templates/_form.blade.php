@php $t = $template ?? null; @endphp
<div class="grid gap-6 xl:grid-cols-[1fr_0.4fr]" x-data="{ lang: 'uk' }">
    <x-card>
        <div class="space-y-4">
            <div>
                <label class="fin-label">Назва шаблону</label>
                <input name="title" type="text" class="fin-input" value="{{ old('title', $t->title ?? '') }}" placeholder="Напр. Затримка платежу" required>
            </div>

            {{-- Language tabs --}}
            <div class="mb-2 inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                @foreach(['uk'=>'UA','en'=>'EN','pl'=>'PL','ru'=>'RU'] as $code=>$lbl)
                    <button type="button" @click="lang='{{ $code }}'" :class="lang==='{{ $code }}' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500'" class="rounded-lg px-4 py-1.5 text-sm font-bold transition">{{ $lbl }}</button>
                @endforeach
            </div>

            <div x-show="lang==='uk'">
                <label class="fin-label">Текст (UA) <span class="text-slate-400">— основний</span></label>
                <textarea name="body" rows="10" class="fin-input" required>{{ old('body', $t->body ?? '') }}</textarea>
            </div>
            <div x-show="lang==='en'" x-cloak>
                <label class="fin-label">Текст (EN)</label>
                <textarea name="body_en" rows="10" class="fin-input">{{ old('body_en', $t->body_en ?? '') }}</textarea>
            </div>
            <div x-show="lang==='pl'" x-cloak>
                <label class="fin-label">Текст (PL)</label>
                <textarea name="body_pl" rows="10" class="fin-input">{{ old('body_pl', $t->body_pl ?? '') }}</textarea>
            </div>
            <div x-show="lang==='ru'" x-cloak>
                <label class="fin-label">Текст (RU)</label>
                <textarea name="body_ru" rows="10" class="fin-input">{{ old('body_ru', $t->body_ru ?? '') }}</textarea>
            </div>
            <p class="text-xs text-slate-400">Порожні мови підставлять UA/EN автоматично під час вставки у відповідь.</p>
        </div>
    </x-card>

    <div class="space-y-5">
        <x-card title="Налаштування">
            <div class="space-y-4">
                <div>
                    <label class="fin-label">Категорія</label>
                    <input name="category" type="text" class="fin-input" value="{{ old('category', $t->category ?? '') }}" placeholder="Напр. Платежі">
                </div>
                <div>
                    <label class="fin-label">Порядок</label>
                    <input name="sort" type="number" min="0" class="fin-input" value="{{ old('sort', $t->sort ?? 0) }}">
                </div>
                <label class="flex cursor-pointer items-center gap-3">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $t->is_active ?? true)) class="rounded border-slate-300 text-blue-600">
                    <span class="text-sm text-slate-700">Активний</span>
                </label>
                <x-button type="submit" icon="save" class="w-full">Зберегти</x-button>
            </div>
        </x-card>
    </div>
</div>
