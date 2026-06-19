@php $m = $module ?? null; @endphp
<div class="grid gap-6 xl:grid-cols-[1fr_0.5fr]" x-data="{ lang: 'uk' }">
    <x-card>
        <div class="space-y-4">
            <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                @foreach(['uk'=>'UA','en'=>'EN','pl'=>'PL'] as $code=>$lbl)
                    <button type="button" @click="lang='{{ $code }}'" :class="lang==='{{ $code }}' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500'" class="rounded-lg px-4 py-1.5 text-sm font-bold transition">{{ $lbl }}</button>
                @endforeach
            </div>

            {{-- UA (base) --}}
            <div x-show="lang==='uk'" class="space-y-4">
                <div>
                    <label class="fin-label">Назва (UA)</label>
                    <input name="name" type="text" class="fin-input @error('name') border-rose-500 @enderror"
                           value="{{ old('name', $m->name ?? '') }}" required placeholder="WooCommerce">
                    @error('name')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="fin-label">Короткий опис (UA) <span class="text-slate-500">(в каталозі)</span></label>
                    <textarea name="description" rows="2" class="fin-input">{{ old('description', $m->description ?? '') }}</textarea>
                </div>
                <div>
                    <label class="fin-label">Повний опис (UA) <span class="text-slate-500">(на сторінці)</span></label>
                    <textarea name="long_description" rows="8" class="fin-input">{{ old('long_description', $m->long_description ?? '') }}</textarea>
                </div>
            </div>
            {{-- EN --}}
            <div x-show="lang==='en'" x-cloak class="space-y-4">
                <div><label class="fin-label">Name (EN)</label><input name="name_en" type="text" class="fin-input" value="{{ old('name_en', $m->name_en ?? '') }}"></div>
                <div><label class="fin-label">Short description (EN)</label><textarea name="description_en" rows="2" class="fin-input">{{ old('description_en', $m->description_en ?? '') }}</textarea></div>
                <div><label class="fin-label">Full description (EN)</label><textarea name="long_description_en" rows="8" class="fin-input">{{ old('long_description_en', $m->long_description_en ?? '') }}</textarea></div>
            </div>
            {{-- PL --}}
            <div x-show="lang==='pl'" x-cloak class="space-y-4">
                <div><label class="fin-label">Nazwa (PL)</label><input name="name_pl" type="text" class="fin-input" value="{{ old('name_pl', $m->name_pl ?? '') }}"></div>
                <div><label class="fin-label">Krótki opis (PL)</label><textarea name="description_pl" rows="2" class="fin-input">{{ old('description_pl', $m->description_pl ?? '') }}</textarea></div>
                <div><label class="fin-label">Pełny opis (PL)</label><textarea name="long_description_pl" rows="8" class="fin-input">{{ old('long_description_pl', $m->long_description_pl ?? '') }}</textarea></div>
            </div>
            <p class="text-xs text-slate-400">EN/PL необов’язкові — якщо порожні, показується українська версія.</p>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="fin-label">Slug <span class="text-slate-500">(авто)</span></label>
                    <input name="slug" type="text" class="fin-input" value="{{ old('slug', $m->slug ?? '') }}" placeholder="woocommerce">
                    @error('slug')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="fin-label">Версія</label>
                    <input name="version" type="text" class="fin-input" value="{{ old('version', $m->version ?? '') }}" placeholder="1.0.0">
                </div>
            </div>
            <div>
                <label class="fin-label">Іконка <span class="text-slate-500">(globe, layout, layers…)</span></label>
                <input name="icon" type="text" class="fin-input" value="{{ old('icon', $m->icon ?? 'layout') }}" placeholder="layout">
            </div>
            <div>
                <label class="fin-label">Фото модуля <span class="text-slate-500">(JPG/PNG/WEBP, до 5 МБ)</span></label>
                <input name="image" type="file" accept="image/jpeg,image/png,image/webp" class="fin-input @error('image') border-rose-500 @enderror">
                @error('image')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                @if($m && $m->image_path)
                    <img src="{{ $m->imageUrl() }}" alt="" class="mt-3 aspect-video w-full max-w-xs rounded-xl border border-slate-200 object-cover">
                @endif
            </div>
        </div>
    </x-card>

    <x-card title="Завантаження">
        <div class="space-y-4">
            <div>
                <label class="fin-label">Файл модуля <span class="text-slate-500">(zip, до 50 МБ)</span></label>
                <input name="file" type="file" class="fin-input @error('file') border-rose-500 @enderror">
                @error('file')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                @if($m && $m->file_path)
                    <p class="mt-1 text-xs text-emerald-600">Завантажено: {{ basename($m->file_path) }} — новий файл замінить його.</p>
                @endif
            </div>
            <div>
                <label class="fin-label">Або зовнішнє посилання</label>
                <input name="external_url" type="url" class="fin-input @error('external_url') border-rose-500 @enderror"
                       value="{{ old('external_url', $m->external_url ?? '') }}" placeholder="https://github.com/...">
                @error('external_url')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="fin-label">Порядок</label>
                <input name="sort" type="number" min="0" class="fin-input" value="{{ old('sort', $m->sort ?? 0) }}">
            </div>
            <label class="flex cursor-pointer items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $m->is_active ?? true))
                       class="rounded border-slate-300 text-blue-600">
                <span class="text-sm text-slate-700">Доступний для завантаження</span>
            </label>
            <x-button type="submit" icon="save" class="w-full">{{ $m ? 'Зберегти' : 'Створити модуль' }}</x-button>
        </div>
    </x-card>
</div>
