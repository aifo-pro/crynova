@props(['id' => 'code-'.uniqid(), 'lang' => 'JSON'])

<div class="group relative mt-3 overflow-hidden rounded-2xl border border-slate-800 bg-slate-950">
    <div class="flex items-center justify-between border-b border-white/10 px-4 py-2">
        <span class="font-mono text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ $lang }}</span>
        <button type="button" @click="copy('{{ $id }}')"
                class="inline-flex items-center gap-1 rounded-lg bg-white/10 px-2 py-1 text-[11px] font-semibold text-slate-200 transition hover:bg-white/20">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            Copy
        </button>
    </div>
    <pre class="overflow-x-auto px-4 py-3.5 text-xs leading-6 text-slate-100"><code id="{{ $id }}">{{ $slot }}</code></pre>
</div>
