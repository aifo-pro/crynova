@props(['target'])
<button type="button" data-copy-target="{{ $target }}" {{ $attributes->merge(['class' => 'inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-blue-300 hover:text-blue-600 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-300 dark:hover:border-blue-400/60 dark:hover:text-white']) }} title="Copy">
    <x-icon name="copy" class="h-4 w-4" />
</button>
