@props(['variant' => 'primary', 'href' => null, 'type' => 'button', 'icon' => null])
@php
    $base = 'inline-flex min-h-10 items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition duration-200 focus:outline-none focus:ring-4 focus:ring-blue-500/15 disabled:pointer-events-none disabled:opacity-50';
    $variants = [
        'primary' => 'brand-button hover:brightness-105',
        'secondary' => 'border border-slate-200 bg-white text-slate-900 hover:border-blue-200 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800',
        'ghost' => 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-white',
        'danger' => 'border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200',
        'warning' => 'border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100',
    ];
    $class = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>
        @if($icon)<x-icon :name="$icon" class="h-4 w-4" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>
        @if($icon)<x-icon :name="$icon" class="h-4 w-4" />@endif
        {{ $slot }}
    </button>
@endif
