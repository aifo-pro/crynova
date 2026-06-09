@props(['variant' => 'info', 'title' => null])
@php
    $classes = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100',
        'error' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100',
        'info' => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-100',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'rounded-lg border p-4 text-sm '.($classes[$variant] ?? $classes['info'])]) }}>
    @if($title)<p class="mb-1 font-semibold">{{ $title }}</p>@endif
    {{ $slot }}
</div>
