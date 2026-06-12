{{-- Rich-text editor (Quill) for the blog body. Expects $content (raw HTML). --}}
@props(['content' => ''])

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">

<div class="blog-editor rounded-2xl border border-slate-200 bg-white">
    <div id="editor-toolbar">
        <span class="ql-formats">
            <select class="ql-header">
                <option value="2">Заголовок</option>
                <option value="3">Підзаголовок</option>
                <option selected>Текст</option>
            </select>
            <select class="ql-font"></select>
            <select class="ql-size"></select>
        </span>
        <span class="ql-formats">
            <button class="ql-bold"></button>
            <button class="ql-italic"></button>
            <button class="ql-underline"></button>
            <button class="ql-strike"></button>
        </span>
        <span class="ql-formats">
            <select class="ql-color"></select>
            <select class="ql-background"></select>
        </span>
        <span class="ql-formats">
            <button class="ql-list" value="ordered"></button>
            <button class="ql-list" value="bullet"></button>
            <button class="ql-blockquote"></button>
            <button class="ql-code-block"></button>
        </span>
        <span class="ql-formats">
            <select class="ql-align"></select>
        </span>
        <span class="ql-formats">
            <button class="ql-link"></button>
            <button class="ql-image"></button>
        </span>
        <span class="ql-formats">
            <button class="ql-clean"></button>
        </span>
    </div>
    <div id="editor" style="min-height: 360px;"></div>
</div>

{{-- Real form field, kept in sync with the editor --}}
<textarea name="body" id="body-input" class="hidden">{{ old('body', $content) }}</textarea>

<style>
    .blog-editor .ql-toolbar.ql-snow,
    .blog-editor #editor-toolbar { border: 0; border-bottom: 1px solid #e2e8f0; border-radius: 1rem 1rem 0 0; }
    .blog-editor .ql-container.ql-snow { border: 0; font-size: 1rem; }
    .blog-editor .ql-editor { min-height: 360px; }
    .blog-editor .ql-editor h2 { font-size: 1.6rem; font-weight: 800; }
    .blog-editor .ql-editor h3 { font-size: 1.25rem; font-weight: 700; }
    .blog-editor .ql-editor img { border-radius: 0.75rem; }
</style>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('body-input');
    const quill = new Quill('#editor', {
        modules: { toolbar: '#editor-toolbar' },
        theme: 'snow',
        placeholder: 'Напишіть статтю… Використовуйте панель для форматування та вставки фото.',
    });

    // Load existing content
    if (input.value.trim() !== '') {
        quill.clipboard.dangerouslyPasteHTML(input.value);
    }

    // Keep the hidden textarea in sync
    function sync() { input.value = quill.root.innerHTML; }
    quill.on('text-change', sync);
    sync();
    input.closest('form').addEventListener('submit', sync);

    // Custom image handler → upload to server, insert returned URL
    quill.getModule('toolbar').addHandler('image', function () {
        const fileInput = document.createElement('input');
        fileInput.setAttribute('type', 'file');
        fileInput.setAttribute('accept', 'image/*');
        fileInput.click();
        fileInput.onchange = async () => {
            const file = fileInput.files[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('image', file);
            try {
                const res = await fetch('{{ route('admin.blog.upload-image') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: fd,
                });
                const data = await res.json();
                if (data.url) {
                    const range = quill.getSelection(true);
                    quill.insertEmbed(range.index, 'image', data.url, 'user');
                    quill.setSelection(range.index + 1);
                }
            } catch (e) { alert('Не вдалося завантажити зображення.'); }
        };
    });
});
</script>
