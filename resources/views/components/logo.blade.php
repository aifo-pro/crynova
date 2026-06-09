@props([
    'variant' => 'full',
])

@php
    $classes = $variant === 'mark'
        ? 'h-9 w-9 rounded-xl object-cover shadow-lg shadow-blue-600/15'
        : 'h-10 w-auto max-w-[180px] object-contain';
@endphp

<img
    src="{{ asset('assets/crynova/logo-light.png') }}"
    alt="Crynova"
    {{ $attributes->merge(['class' => $classes]) }}
>
