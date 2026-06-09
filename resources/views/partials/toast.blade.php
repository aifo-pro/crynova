{{-- Глобальные всплывающие уведомления (auto-dismiss ~3.2s) --}}
@php
    $toasts = [];
    foreach (['success' => 'success', 'error' => 'error', 'warning' => 'warning', 'info' => 'info'] as $key => $type) {
        if (session()->has($key)) {
            $toasts[] = ['type' => $type, 'msg' => session($key)];
        }
    }
@endphp
@if(!empty($toasts))
<div class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6">
    @foreach($toasts as $i => $t)
    @php
        $styles = [
            'success' => ['bg-emerald-600', 'M20 6 9 17l-5-5'],
            'error'   => ['bg-rose-600',    'M18 6 6 18M6 6l12 12'],
            'warning' => ['bg-amber-500',   'M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z'],
            'info'    => ['bg-blue-600',     'M12 16v-4M12 8h.01M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z'],
        ];
        [$bg, $path] = $styles[$t['type']] ?? $styles['info'];
    @endphp
    <div x-data="{ show: false }"
         x-init="$nextTick(() => show = true); setTimeout(() => show = false, 3200)"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-3 sm:translate-x-3 sm:translate-y-0"
         x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0 -translate-y-2"
         @click="show = false"
         class="pointer-events-auto relative flex w-full max-w-sm cursor-pointer items-start gap-3 overflow-hidden rounded-2xl bg-white px-4 py-3 shadow-2xl shadow-slate-400/30 ring-1 ring-slate-100">
        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $bg }} text-white">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}"/></svg>
        </span>
        <p class="flex-1 text-sm font-medium leading-snug text-slate-800">{{ $t['msg'] }}</p>
        <button type="button" @click.stop="show = false" class="shrink-0 text-slate-300 hover:text-slate-600">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round"/></svg>
        </button>
        {{-- progress bar --}}
        <span class="absolute inset-x-0 bottom-0 h-0.5 overflow-hidden rounded-b-2xl">
            <span class="block h-full {{ $bg }} opacity-60" x-init="$el.animate([{width:'100%'},{width:'0%'}], {duration:3200, easing:'linear'})" style="width:100%"></span>
        </span>
    </div>
    @endforeach
</div>
@endif
