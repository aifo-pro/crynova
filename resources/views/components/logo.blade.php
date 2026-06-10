@props([
    'variant' => 'full',
])

@php
    $src = $variant === 'mark'
        ? asset('favicon.svg')
        : asset('assets/crynova/logo-light.png');

    $classes = match ($variant) {
        'mark'   => 'h-9 w-9 rounded-xl object-contain shadow-lg shadow-blue-600/15',
        'header' => 'brand-logo-header',
        default  => 'h-10 w-auto max-w-[180px] object-contain object-left',
    };
@endphp

<img
    src="{{ $src }}"
    alt="Crynova"
    {{ $attributes->merge(['class' => $classes]) }}
>
