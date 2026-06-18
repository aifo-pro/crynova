@props(['compact' => false])
@php
    $currentLocale = app()->getLocale();

    // On localized public pages the language is part of the URL, so switching
    // means navigating to the prefixed URL. In the cabinet/auth area there are
    // no localized URLs, so switching stores the choice in the session.
    $localizedNames = [
        'home', 'pricing', 'coins', 'developers', 'api.docs',
        'contact', 'blog', 'blog.show', 'pages.show',
        'legal.terms', 'legal.privacy', 'legal.aml-kyc', 'legal.risk-disclosure',
    ];
    $onPublic = in_array(request()->route()?->getName(), $localizedNames, true)
        || in_array(request()->segment(1), ['en', 'pl'], true);
@endphp

@if($compact)
<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <span class="text-slate-400">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/></svg>
    </span>
    <div class="inline-flex rounded-full border border-slate-200 bg-white p-0.5 shadow-sm">
        @foreach(['uk' => 'UA', 'en' => 'EN', 'pl' => 'PL'] as $locale => $label)
            @if($onPublic)
                <a href="{{ locale_path($locale) }}" class="rounded-full px-3 py-1 text-xs font-bold transition {{ $currentLocale === $locale ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">{{ $label }}</a>
            @else
                <form method="POST" action="{{ route('locale.switch', $locale) }}">
                    @csrf
                    <button type="submit" class="rounded-full px-3 py-1 text-xs font-bold transition {{ $currentLocale === $locale ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">{{ $label }}</button>
                </form>
            @endif
        @endforeach
    </div>
</div>
@else
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-2']) }}>
    <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('ui.language') }}</p>
    <div class="grid grid-cols-3 gap-1">
        @foreach(['uk' => 'UA', 'en' => 'EN', 'pl' => 'PL'] as $locale => $label)
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
