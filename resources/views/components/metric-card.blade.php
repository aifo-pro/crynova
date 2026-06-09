@props(['label', 'value', 'trend' => null, 'icon' => 'gauge'])
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-950/72 dark:shadow-black/15']) }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $label }}</p>
            <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ $value }}</p>
        </div>
        <div class="rounded-xl border border-blue-100 bg-blue-50 p-2 text-blue-600 dark:border-blue-400/20 dark:bg-blue-400/10 dark:text-blue-200">
            <x-icon :name="$icon" class="h-5 w-5" />
        </div>
    </div>
    @if($trend)<p class="mt-4 text-xs text-blue-600 dark:text-blue-200">{{ $trend }}</p>@endif
</div>
