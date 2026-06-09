@props(['id' => 'crypto-address', 'label' => 'Address', 'value' => '', 'accent' => 'teal'])
<div>
    <label class="fin-label">{{ $label }}</label>
    <div class="flex items-stretch gap-2">
        <code id="{{ $id }}" class="flex min-h-12 flex-1 items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 font-mono text-sm text-blue-700 break-all dark:border-slate-800 dark:bg-slate-950 dark:text-teal-200">
            {{ $value }}
        </code>
        <x-copy-button :target="$id" />
    </div>
</div>
