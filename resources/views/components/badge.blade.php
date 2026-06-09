@props(['variant' => 'slate'])
@php
    $variants = [
        'slate' => 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200',
        'teal' => 'border-cyan-200 bg-cyan-50 text-cyan-700 dark:border-cyan-400/30 dark:bg-cyan-400/10 dark:text-cyan-200',
        'green' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-400/30 dark:bg-emerald-400/10 dark:text-emerald-200',
        'yellow' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-200',
        'red' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-400/30 dark:bg-rose-400/10 dark:text-rose-200',
        'blue' => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-sky-400/30 dark:bg-sky-400/10 dark:text-sky-200',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-semibold '.($variants[$variant] ?? $variants['slate'])]) }}>
    {{ $slot }}
</span>
