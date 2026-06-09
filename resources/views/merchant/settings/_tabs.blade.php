@php
    $tabs = [
        [__('merchant_settings.tabs.project'), 'merchant.settings.project'],
        [__('merchant_settings.tabs.integration'), 'merchant.settings.integration'],
        [__('merchant_settings.tabs.currencies'), 'merchant.settings.currencies'],
        [__('merchant_settings.tabs.fees'), 'merchant.settings.fees'],
        [__('merchant_settings.tabs.wallets'), 'merchant.settings.wallets'],
        [__('merchant_settings.tabs.autoconversion'), 'merchant.settings.autoconversion'],
        [__('merchant_settings.tabs.constructor'), 'merchant.settings.constructor'],
        [__('merchant_settings.tabs.widget'), 'merchant.settings.widget'],
    ];
@endphp

<div class="rounded-3xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
    <p class="px-2 pb-3 text-sm text-slate-400">{{ __('merchant_settings.tabs.select') }}</p>
    <nav class="flex gap-1 overflow-x-auto">
        @foreach($tabs as [$label, $route])
            @php $active = request()->routeIs($route); @endphp
            <a href="{{ route($route, $merchant) }}"
               class="shrink-0 rounded-xl px-4 py-2 text-sm font-medium transition {{ $active ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>
</div>
