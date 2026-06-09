<div class="rounded-3xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
    <p class="px-2 pb-3 text-sm text-slate-400">{{ __('account.section_select') }}</p>
    <nav class="flex flex-wrap gap-1">
        @foreach([
            'account.settings.profile' => __('account.settings.profile'),
            'account.settings.security' => __('account.settings.security'),
            'account.settings.notifications' => __('account.settings.notifications'),
            'account.settings.team' => __('account.settings.team'),
        ] as $route => $label)
            @php $active = request()->routeIs($route); @endphp
            <a href="{{ route($route) }}" class="shrink-0 rounded-xl px-4 py-2 text-sm font-medium transition {{ $active ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">{{ $label }}</a>
        @endforeach
    </nav>
</div>
