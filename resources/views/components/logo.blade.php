@props([
    'variant' => 'full',
])

@if($variant === 'compact')
    <span {{ $attributes->merge(['class' => 'brand-lockup']) }}>
        <img src="{{ asset('favicon.svg') }}" alt="" class="brand-lockup__mark" width="40" height="40" aria-hidden="true">
        <span class="brand-lockup__word">Crynova</span>
    </span>
@else
    @php
        $src = $variant === 'mark'
            ? asset('favicon.svg')
            : asset('assets/crynova/logo-light.png');

        $classes = $variant === 'mark'
            ? 'h-9 w-9 rounded-xl object-contain shadow-lg shadow-blue-600/15'
            : 'h-10 w-auto max-w-[180px] object-contain object-left';
    @endphp

    <img
        src="{{ $src }}"
        alt="Crynova"
        {{ $attributes->merge(['class' => $classes]) }}
    >
@endif
