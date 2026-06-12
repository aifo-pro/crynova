@props([
    'variant' => 'full',
])

@php
    $icon = asset('assets/crynova/icon-logo.png');

    // Icon size per context
    $iconClass = match ($variant) {
        'mark'   => 'h-9 w-9',
        'header' => 'h-9 w-9 sm:h-10 sm:w-10',
        default  => 'h-9 w-9 sm:h-10 sm:w-10',
    };
@endphp

@if($variant === 'mark')
    {{-- Icon only (e.g. checkout header) --}}
    <img src="{{ $icon }}" alt="Crynova"
         {{ $attributes->merge(['class' => 'shrink-0 rounded-xl object-contain '.$iconClass]) }}>
@else
    {{-- Icon + wordmark --}}
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
        <img src="{{ $icon }}" alt="" class="shrink-0 object-contain {{ $iconClass }}">
        <span class="brand-wordmark select-none text-xl font-black leading-none tracking-[-0.01em] sm:text-2xl">CRYNOVA</span>
    </span>
@endif
