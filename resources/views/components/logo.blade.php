@props([
    'variant' => 'full',
])

@php
    $src = $variant === 'mark'
        ? asset('favicon.svg')
        : asset('assets/crynova/logo-light.png');

    $classes = $variant === 'mark'
        ? 'h-9 w-9 rounded-xl object-contain shadow-lg shadow-blue-600/15'
        : 'h-10 w-auto max-w-[180px] object-contain';
@endphp

<img
    src="{{ $src }}"
    alt="Crynova"
    {{ $attributes->merge(['class' => $classes]) }}
>
