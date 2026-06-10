@php $n = $item ?? null; @endphp
<div class="grid gap-6 xl:grid-cols-[1fr_0.4fr]">
    <x-card>
        <div class="space-y-4">
            <div>
                <label class="fin-label">Заголовок</label>
                <input name="title" type="text" class="fin-input @error('title') border-rose-400 @enderror"
                       value="{{ old('title', $n->title ?? '') }}" required placeholder="Crynova додав підтримку нової мережі">
                @error('title')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="fin-label">Анонс <span class="text-slate-400">(показується у списку)</span></label>
                <textarea name="excerpt" rows="2" class="fin-input @error('excerpt') border-rose-400 @enderror" placeholder="Короткий опис…">{{ old('excerpt', $n->excerpt ?? '') }}</textarea>
                @error('excerpt')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="fin-label">Текст <span class="text-slate-400">(HTML або звичайний текст)</span></label>
                <textarea name="body" rows="16" class="fin-input font-mono text-sm @error('body') border-rose-400 @enderror" required placeholder="<p>...</p>">{{ old('body', $n->body ?? '') }}</textarea>
                @error('body')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
        </div>
    </x-card>

    <x-card title="Публікація">
        <div class="space-y-4">
            <div>
                <label class="fin-label">Статус</label>
                <select name="status" class="fin-input">
                    @foreach(['draft'=>'Чернетка','published'=>'Опубліковано','archived'=>'Архів'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('status', $n->status ?? 'draft') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="fin-label">URL обкладинки</label>
                <input name="cover_image" type="url" class="fin-input @error('cover_image') border-rose-400 @enderror"
                       value="{{ old('cover_image', $n->cover_image ?? '') }}" placeholder="https://...">
                @error('cover_image')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <x-button type="submit" icon="save" class="w-full">{{ $n ? 'Зберегти' : 'Опублікувати' }}</x-button>
        </div>
    </x-card>
</div>
