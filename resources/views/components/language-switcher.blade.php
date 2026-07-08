@props(['compact' => false, 'drop' => 'up'])
@php $dropUp = $drop !== 'down'; @endphp
@php
    $currentLocale = app()->getLocale();

    // On localized public pages the language is part of the URL, so switching
    // means navigating to the prefixed URL. In the cabinet/auth area there are
    // no localized URLs, so switching stores the choice in the session.
    $localizedNames = [
        'home', 'pricing', 'coins', 'developers', 'api.docs', 'api.sdk',
        'contact', 'blog', 'blog.show', 'pages.show',
        'legal.terms', 'legal.privacy', 'legal.aml-kyc', 'legal.risk-disclosure',
    ];
    $onPublic = in_array(request()->route()?->getName(), $localizedNames, true)
        || in_array(request()->segment(1), ['en', 'pl', 'ru'], true);

    $langs = ['uk' => 'Українська', 'en' => 'English', 'pl' => 'Polski', 'ru' => 'Русский'];
    $codes = ['uk' => 'UA', 'en' => 'EN', 'pl' => 'PL', 'ru' => 'RU'];

    // Inline SVG flags (public-domain national flags) — identical on every OS.
    $flagSvg = [
        'uk' => '<svg viewBox="0 0 24 16" class="h-full w-full"><rect width="24" height="8" fill="#0057b7"/><rect y="8" width="24" height="8" fill="#ffd700"/></svg>',
        'pl' => '<svg viewBox="0 0 24 16" class="h-full w-full"><rect width="24" height="8" fill="#fff"/><rect y="8" width="24" height="8" fill="#dc143c"/></svg>',
        'ru' => '<svg viewBox="0 0 24 16" class="h-full w-full"><rect width="24" height="5.34" fill="#fff"/><rect y="5.34" width="24" height="5.33" fill="#0039a6"/><rect y="10.67" width="24" height="5.33" fill="#d52b1e"/></svg>',
        'en' => '<svg viewBox="0 0 24 16" class="h-full w-full"><rect width="24" height="16" fill="#012169"/><path d="M0 0 24 16M24 0 0 16" stroke="#fff" stroke-width="3.2"/><path d="M0 0 24 16M24 0 0 16" stroke="#c8102e" stroke-width="1.6"/><path d="M12 0V16M0 8H24" stroke="#fff" stroke-width="5"/><path d="M12 0V16M0 8H24" stroke="#c8102e" stroke-width="3"/></svg>',
    ];
    $flag = fn ($l, $cls) => '<span class="inline-flex shrink-0 items-center overflow-hidden rounded-[3px] align-middle ring-1 ring-black/5 '.$cls.'">'.($flagSvg[$l] ?? '').'</span>';
@endphp

@if($compact)
{{-- Compact dropdown: a chip with the current flag, expands to a centered menu. --}}
<div {{ $attributes->merge(['class' => 'relative inline-block text-left']) }} x-data="{ open: false }">
    <button type="button" @click="open = !open" @click.outside="open = false"
            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-slate-50">
        {!! $flag($currentLocale, 'h-3.5 w-5') !!}
        <span>{{ $codes[$currentLocale] ?? 'UA' }}</span>
        <svg viewBox="0 0 20 20" width="12" height="12" fill="currentColor" class="text-slate-400 transition" :class="open ? 'rotate-180' : ''"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
    </button>
    <div x-show="open" x-cloak x-transition.scale.origin.{{ $dropUp ? 'bottom' : 'top' }}
         class="absolute {{ $dropUp ? 'bottom-full mb-2' : 'top-full mt-2' }} left-1/2 z-30 w-48 -translate-x-1/2 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl shadow-slate-300/40">
        @foreach($langs as $locale => $name)
            @php $item = 'flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-semibold transition '.($currentLocale === $locale ? 'bg-blue-50 text-blue-600' : 'text-slate-600 hover:bg-slate-50'); @endphp
            @if($onPublic)
                <a href="{{ locale_path($locale) }}" class="{{ $item }}">
                    {!! $flag($locale, 'h-4 w-6') !!}
                    <span class="flex-1">{{ $name }}</span>
                    @if($currentLocale === $locale)<svg viewBox="0 0 20 20" width="14" height="14" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.1 3.1 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>@endif
                </a>
            @else
                <form method="POST" action="{{ route('locale.switch', $locale) }}">
                    @csrf
                    <button type="submit" class="w-full text-left {{ $item }}">
                        {!! $flag($locale, 'h-4 w-6') !!}
                        <span class="flex-1">{{ $name }}</span>
                        @if($currentLocale === $locale)<svg viewBox="0 0 20 20" width="14" height="14" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.1 3.1 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>@endif
                    </button>
                </form>
            @endif
        @endforeach
    </div>
</div>
@else
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-2']) }}>
    <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('ui.language') }}</p>
    <div class="grid grid-cols-2 gap-1">
        @foreach($langs as $locale => $name)
            @php $cell = 'flex h-9 w-full items-center gap-2 rounded-xl px-2.5 text-sm font-semibold transition '.($currentLocale === $locale ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'); @endphp
            @if($onPublic)
                <a href="{{ locale_path($locale) }}" class="{{ $cell }}">{!! $flag($locale, 'h-3.5 w-5') !!} {{ $codes[$locale] }}</a>
            @else
                <form method="POST" action="{{ route('locale.switch', $locale) }}">
                    @csrf
                    <button type="submit" class="{{ $cell }}">{!! $flag($locale, 'h-3.5 w-5') !!} {{ $codes[$locale] }}</button>
                </form>
            @endif
        @endforeach
    </div>
</div>
@endif
