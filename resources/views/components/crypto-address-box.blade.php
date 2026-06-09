@props(['id' => 'crypto-address', 'label' => 'Address', 'value' => '', 'accent' => 'teal'])
<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <label class="fin-label">{{ $label }}</label>
    <div class="flex min-w-0 items-stretch gap-2">
        <code id="{{ $id }}" class="flex min-h-12 min-w-0 flex-1 items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 font-mono text-sm leading-5 text-blue-700 break-all whitespace-normal dark:border-slate-800 dark:bg-slate-950 dark:text-teal-200">
            {{ $value }}
        </code>
        <x-copy-button :target="$id" class="h-auto min-h-12 w-12 shrink-0" />
    </div>
</div>
