@props(['title' => null, 'subtitle' => null])
<section {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white/90 shadow-xl shadow-slate-200/60 backdrop-blur dark:border-slate-800 dark:bg-slate-950/72 dark:shadow-black/20']) }}>
    @if($title || $subtitle || isset($actions))
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
            <div>
                @if($title)<h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ $title }}</h2>@endif
                @if($subtitle)<p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div class="{{ ($title || $subtitle || isset($actions)) ? 'p-5' : 'p-5' }}">
        {{ $slot }}
    </div>
</section>
