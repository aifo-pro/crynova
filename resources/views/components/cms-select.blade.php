@props([
    'name' => 'cms',
    'options' => [],         // list of CMS names
    'selected' => null,
    'placeholder' => 'CMS',
])
@php
    // Brand colour + short mark for each CMS (rendered as a rounded badge).
    $meta = [
        'WordPress'   => ['#21759b', 'W'],
        'WooCommerce' => ['#7f54b3', 'Wo'],
        'OpenCart'    => ['#23a2d9', 'O'],
        'Tilda'       => ['#ffa282', 'T'],
        'Bitrix'      => ['#00aeef', 'B'],
        'PrestaShop'  => ['#df0067', 'P'],
        'Magento'     => ['#ee672f', 'M'],
        'Other CMS'   => ['#64748b', '…'],
    ];
    $badge = function ($cms) use ($meta) {
        [$color, $mark] = $meta[$cms] ?? ['#64748b', mb_substr($cms, 0, 1)];
        return [$color, $mark];
    };
    $initial = $selected !== null && $selected !== '' ? "'".addslashes($selected)."'" : 'null';
@endphp
<div x-data="{ open: false, sel: {{ $initial }} }" class="relative" @keydown.escape="open=false">
    <input type="hidden" name="{{ $name }}" :value="sel ?? ''">

    {{-- Trigger --}}
    <button type="button" @click="open=!open" class="fin-input flex w-full items-center justify-between gap-3 pr-4 text-left">
        <span class="flex min-w-0 items-center gap-2.5">
            @foreach($options as $cms)
                @php [$color, $mark] = $badge($cms); @endphp
                <span x-show="sel === @js($cms)" class="flex min-w-0 items-center gap-2.5">
                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-md text-[10px] font-black text-white" style="background-color: {{ $color }}">{{ $mark }}</span>
                    <span class="truncate font-semibold text-slate-900">{{ $cms }}</span>
                </span>
            @endforeach
            <span x-show="sel === null || sel === ''" class="text-slate-400">{{ $placeholder }}</span>
        </span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
    </button>

    {{-- Dropdown --}}
    <div x-show="open" x-cloak @click.outside="open=false" x-transition.opacity
         class="absolute left-0 z-30 mt-1 max-h-72 w-full overflow-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
        <button type="button" @click="sel=null; open=false"
                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-slate-400 transition hover:bg-slate-50"
                :class="(sel === null || sel === '') ? 'bg-blue-50' : ''">{{ $placeholder }}</button>
        @foreach($options as $cms)
            @php [$color, $mark] = $badge($cms); @endphp
            <button type="button" @click="sel=@js($cms); open=false"
                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left transition hover:bg-slate-50"
                    :class="sel === @js($cms) ? 'bg-blue-50' : ''">
                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-md text-[10px] font-black text-white" style="background-color: {{ $color }}">{{ $mark }}</span>
                <span class="truncate font-semibold text-slate-900">{{ $cms }}</span>
            </button>
        @endforeach
    </div>
</div>
