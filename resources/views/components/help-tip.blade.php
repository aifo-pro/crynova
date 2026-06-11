@props(['text' => ''])
<span x-data="{ open: false }" class="relative ml-1 inline-flex align-middle"
      @mouseenter="open=true" @mouseleave="open=false">
    <button type="button" @click="open=!open" @keydown.escape="open=false"
            :aria-expanded="open" aria-label="?"
            class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-400 transition hover:bg-blue-100 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200">
        ?
    </button>
    <span x-show="open" x-cloak x-transition.opacity @click.outside="open=false"
          class="absolute left-1/2 top-full z-40 mt-2 w-64 -translate-x-1/2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-normal leading-5 text-slate-600 shadow-xl">
        <span class="absolute -top-1.5 left-1/2 h-3 w-3 -translate-x-1/2 rotate-45 border-l border-t border-slate-200 bg-white"></span>
        {{ $text ?: $slot }}
    </span>
</span>
