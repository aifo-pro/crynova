@props(['compact' => false])
@php($currentLocale = app()->getLocale())

@if($compact)
<div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3']) }}>
    <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('ui.language') }}</span>
    <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-0.5">
        @foreach(['uk' => 'UA', 'en' => 'EN'] as $locale => $label)
            <form method="POST" action="{{ route('locale.switch', $locale) }}">
                @csrf
                <button type="submit" class="min-w-[2.75rem] rounded-lg px-2.5 py-1.5 text-xs font-semibold transition {{ $currentLocale === $locale ? 'bg-blue-600 text-white shadow-sm shadow-blue-600/20' : 'text-slate-600 hover:text-slate-950' }}">
                    {{ $label }}
                </button>
            </form>
        @endforeach
    </div>
</div>
@else
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-2']) }}>
    <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('ui.language') }}</p>
    <div class="grid grid-cols-2 gap-1">
        @foreach(['uk' => 'UA', 'en' => 'EN'] as $locale => $label)
            <form method="POST" action="{{ route('locale.switch', $locale) }}">
                @csrf
                <button type="submit" class="h-9 w-full rounded-xl text-sm font-semibold transition {{ $currentLocale === $locale ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                    {{ $label }}
                </button>
            </form>
        @endforeach
    </div>
</div>
@endif
