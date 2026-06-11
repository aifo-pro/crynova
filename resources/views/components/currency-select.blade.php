@props([
    'name' => 'currency_id',
    'currencies' => [],
    'required' => false,
])
@php $first = optional($currencies->first())->id; @endphp
<div x-data="{ open: false, sel: {{ $first ?? 'null' }} }" class="relative" @keydown.escape="open=false">
    <input type="hidden" name="{{ $name }}" :value="sel" @if($required) required @endif>

    {{-- Trigger --}}
    <button type="button" @click="open=!open"
            class="fin-input flex w-full items-center justify-between gap-2 text-left">
        <span class="flex min-w-0 items-center gap-2">
            @foreach($currencies as $c)
                <span x-show="sel == {{ $c->id }}" class="flex min-w-0 items-center gap-2">
                    <x-coin-icon :code="$c->code" class="h-6 w-6" />
                    <span class="truncate font-semibold text-slate-900">{{ $c->code }}</span>
                </span>
            @endforeach
        </span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
    </button>

    {{-- Dropdown --}}
    <div x-show="open" x-cloak @click.outside="open=false" x-transition.opacity
         class="absolute left-0 z-30 mt-1 max-h-64 w-full overflow-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
        @foreach($currencies as $c)
            <button type="button" @click="sel={{ $c->id }}; open=false"
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left transition hover:bg-slate-50"
                    :class="sel == {{ $c->id }} ? 'bg-blue-50' : ''">
                <x-coin-icon :code="$c->code" class="h-6 w-6" />
                <span class="font-semibold text-slate-900">{{ $c->code }}</span>
                <span class="truncate text-xs text-slate-400">{{ $c->name }}</span>
            </button>
        @endforeach
    </div>
</div>
