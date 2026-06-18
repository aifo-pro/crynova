@php $m = $module ?? null; @endphp
<div class="grid gap-6 xl:grid-cols-[1fr_0.5fr]">
    <x-card>
        <div class="space-y-4">
            <div>
                <label class="fin-label">Назва</label>
                <input name="name" type="text" class="fin-input @error('name') border-rose-500 @enderror"
                       value="{{ old('name', $m->name ?? '') }}" required placeholder="WordPress / WooCommerce">
                @error('name')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="fin-label">Короткий опис <span class="text-slate-500">(в каталозі)</span></label>
                <textarea name="description" rows="2" class="fin-input @error('description') border-rose-500 @enderror"
                          placeholder="Плагін для приймання криптоплатежів у WooCommerce.">{{ old('description', $m->description ?? '') }}</textarea>
                @error('description')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="fin-label">Повний опис <span class="text-slate-500">(на сторінці модуля)</span></label>
                <textarea name="long_description" rows="8" class="fin-input @error('long_description') border-rose-500 @enderror"
                          placeholder="Детальний опис можливостей, кроки встановлення, вимоги…">{{ old('long_description', $m->long_description ?? '') }}</textarea>
                @error('long_description')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
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
