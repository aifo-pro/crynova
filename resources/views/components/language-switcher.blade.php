@props(['compact' => false])
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
    $currentLabel = $codes[$currentLocale] ?? 'UA';
@endphp

@if($compact)
{{-- Compact dropdown: a single chip showing the current language, expands to a menu. --}}
<div {{ $attributes->merge(['class' => 'relative inline-block text-left']) }} x-data="{ open: false }">
    <button type="button" @click="open = !open" @click.outside="open = false"
            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:border-blue-200 hover:text-blue-600">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/></svg>
        <span>{{ $currentLabel }}</span>
        <svg viewBox="0 0 20 20" width="12" height="12" fill="currentColor" class="text-slate-400 transition" :class="open ? 'rotate-180' : ''"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
    </button>
    <div x-show="open" x-cloak x-transition.opacity
         class="absolute right-0 bottom-full z-30 mb-2 w-40 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1 shadow-xl shadow-slate-200/60">
        @foreach($langs as $locale => $name)
            @if($onPublic)
                <a href="{{ locale_path($locale) }}"
                   class="flex items-center justify-between rounded-xl px-3 py-2 text-sm font-semibold transition {{ $currentLocale === $locale ? 'bg-blue-50 text-blue-600' : 'text-slate-600 hover:bg-slate-50' }}">
                    {{ $name }} <span class="text-[10px] font-bold text-slate-400">{{ $codes[$locale] }}</span>
                </a>
            @else
                <form method="POST" action="{{ route('locale.switch', $locale) }}">
                    @csrf
                    <button type="submit"
                            class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm font-semibold transition {{ $currentLocale === $locale ? 'bg-blue-50 text-blue-600' : 'text-slate-600 hover:bg-slate-50' }}">
                        {{ $name }} <span class="text-[10px] font-bold text-slate-400">{{ $codes[$locale] }}</span>
                    </button>
                </form>
            @endif
        @endforeach
    </div>
</div>
@else
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-2']) }}>
    <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('ui.language') }}</p>
    <div class="grid grid-cols-4 gap-1">
        @foreach(['uk' => 'UA', 'en' => 'EN', 'pl' => 'PL', 'ru' => 'RU'] as $locale => $label)
            @if($onPublic)
                <a href="{{ locale_path($locale) }}" class="grid h-9 w-full place-items-center rounded-xl text-sm font-semibold transition {{ $currentLocale === $locale ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">{{ $label }}</a>
            @else
                <form method="POST" action="{{ route('locale.switch', $locale) }}">
                    @csrf
                    <button type="submit" class="h-9 w-full rounded-xl text-sm font-semibold transition {{ $currentLocale === $locale ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">{{ $label }}</button>
                </form>
            @endif
        @endforeach
    </div>
</div>
@endif
