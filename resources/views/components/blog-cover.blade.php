@props(['value' => ''])

{{-- Cover image: upload a local file OR paste a URL. Upload wins if both are set. --}}
<div x-data="{
        mode: 'url',
        url: @js(old('cover_image', $value)),
        preview: @js(old('cover_image', $value)),
        pickFile(e) {
            const f = e.target.files[0];
            if (f) { this.preview = URL.createObjectURL(f); }
        }
     }">
    <label class="fin-label">Обкладинка</label>

    <div class="mb-3 inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1 text-sm">
        <button type="button" @click="mode='url'" :class="mode==='url' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500'" class="rounded-lg px-3 py-1.5 font-semibold transition">Посилання</button>
        <button type="button" @click="mode='upload'" :class="mode==='upload' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500'" class="rounded-lg px-3 py-1.5 font-semibold transition">Завантажити</button>
    </div>

    {{-- URL mode --}}
    <div x-show="mode==='url'">
        <input name="cover_image" type="url" x-model="url" @input="preview = url"
               class="fin-input @error('cover_image') border-rose-500 @enderror" placeholder="https://...">
        @error('cover_image')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
    </div>

    {{-- Upload mode --}}
    <div x-show="mode==='upload'" x-cloak>
        {{-- Hidden empty cover_image so a previously-pasted URL is cleared when uploading --}}
        <input type="hidden" name="cover_image" value="">
        <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center transition hover:border-blue-300 hover:bg-blue-50/40">
            <svg class="h-6 w-6 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/></svg>
            <span class="text-sm font-semibold text-slate-600">Натисніть, щоб вибрати файл</span>
            <span class="text-xs text-slate-400">JPG, PNG, WEBP · до 5 МБ</span>
            <input type="file" name="cover_upload" accept="image/jpeg,image/png,image/webp" class="hidden" @change="pickFile">
        </label>
        @error('cover_upload')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
    </div>

    {{-- Live preview --}}
    <template x-if="preview">
        <div class="mt-3">
            <img :src="preview" alt="" class="aspect-video w-full rounded-xl border border-slate-200 object-cover">
        </div>
    </template>
</div>
