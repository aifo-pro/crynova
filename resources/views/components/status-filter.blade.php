@props([
    'name' => 'status',
    'selected' => null,
    'placeholder' => 'Status',
])
@php
    $statuses = ['pending','waiting_confirmations','paid','underpaid','overpaid','expired','failed','refunded'];
    $colors = [
        'pending' => 'bg-amber-400', 'waiting_confirmations' => 'bg-blue-500', 'paid' => 'bg-emerald-500',
        'underpaid' => 'bg-orange-400', 'overpaid' => 'bg-cyan-500', 'expired' => 'bg-slate-400',
        'failed' => 'bg-rose-500', 'refunded' => 'bg-slate-400',
    ];
    $sel = $selected !== null && $selected !== '' ? (string) $selected : '';
@endphp
<div x-data="{ open: false, sel: @js($sel) }" class="relative w-44" @keydown.escape="open=false">
    <input type="hidden" name="{{ $name }}" :value="sel">
    <button type="button" @click="open=!open" class="fin-input flex w-full items-center justify-between gap-2 pr-3 text-left">
        <span class="flex min-w-0 items-center gap-2">
            <span x-show="sel === ''" class="text-slate-400">{{ $placeholder }}</span>
            @foreach($statuses as $st)
                <span x-show="sel === @js($st)" class="flex min-w-0 items-center gap-2">
                    <span class="h-2 w-2 shrink-0 rounded-full {{ $colors[$st] }}"></span>
                    <span class="truncate font-semibold text-slate-900">{{ __('checkout.status.'.$st) }}</span>
                </span>
            @endforeach
        </span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
    </button>
    <div x-show="open" x-cloak @click.outside="open=false" x-transition.opacity class="absolute left-0 z-30 mt-1 max-h-72 w-full overflow-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
        <button type="button" @click="sel=''; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-slate-400 transition hover:bg-slate-50" :class="sel === '' ? 'bg-blue-50' : ''">{{ $placeholder }}</button>
        @foreach($statuses as $st)
            <button type="button" @click="sel=@js($st); open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left transition hover:bg-slate-50" :class="sel === @js($st) ? 'bg-blue-50' : ''">
                <span class="h-2 w-2 shrink-0 rounded-full {{ $colors[$st] }}"></span>
                <span class="font-semibold text-slate-900">{{ __('checkout.status.'.$st) }}</span>
            </button>
        @endforeach
    </div>
</div>
