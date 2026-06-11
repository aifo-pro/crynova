@props([
    'name' => 'merchant_id',
    'projects' => [],
    'required' => false,
    'selected' => null,          // pre-selected project id
    'placeholder' => null,       // when set, allows an empty initial state
    'syncId' => null,            // parent Alpine var to mirror the chosen id
    'syncName' => null,          // parent Alpine var to mirror the chosen name
    'submit' => false,           // submit the surrounding form on selection (filters)
])
@php
    $initial = $selected !== null && $selected !== ''
        ? $selected
        : ($placeholder !== null ? 'null' : (optional($projects->first())->id ?? 'null'));
    $tail = '; open=false'.($submit ? "; \$nextTick(() => \$el.closest('form')?.submit())" : '');
    $assign = fn($id, $nameExpr) =>
        "sel={$id}"
        .($syncId  ? "; {$syncId}={$id}" : '')
        .($syncName ? "; {$syncName}={$nameExpr}" : '')
        .$tail;
@endphp
<div x-data="{ open: false, sel: {{ $initial }} }" class="relative" @keydown.escape="open=false">
    <input type="hidden" name="{{ $name }}" :value="sel ?? ''" @if($required) required @endif>

    {{-- Trigger --}}
    <button type="button" @click="open=!open"
            class="fin-input flex w-full items-center justify-between gap-3 pr-4 text-left">
        <span class="flex min-w-0 items-center gap-2">
            @if($placeholder !== null)
                <span x-show="sel === null || sel === ''" class="text-slate-400">{{ $placeholder }}</span>
            @endif
            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-md bg-blue-50 text-[11px] font-bold text-blue-600" x-show="sel !== null && sel !== ''">
                @foreach($projects as $p)<span x-show="sel == {{ $p->id }}">{{ mb_strtoupper(mb_substr($p->name, 0, 1)) }}</span>@endforeach
            </span>
            @foreach($projects as $p)
                <span x-show="sel == {{ $p->id }}" class="truncate font-semibold text-slate-900">{{ $p->name }}</span>
            @endforeach
        </span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
    </button>

    {{-- Dropdown --}}
    <div x-show="open" x-cloak @click.outside="open=false" x-transition.opacity
         class="absolute left-0 z-30 mt-1 max-h-64 w-full overflow-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
        @if($placeholder !== null)
            <button type="button" @click="{{ "sel=null".($syncId ? "; {$syncId}=null" : '').($syncName ? "; {$syncName}=''" : '').$tail }}"
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-slate-400 transition hover:bg-slate-50"
                    :class="(sel === null || sel === '') ? 'bg-blue-50' : ''">{{ $placeholder }}</button>
        @endif
        @foreach($projects as $p)
            <button type="button" @click="{{ $assign($p->id, "'".addslashes($p->name)."'") }}"
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left transition hover:bg-slate-50"
                    :class="sel == {{ $p->id }} ? 'bg-blue-50' : ''">
                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-md bg-blue-50 text-[11px] font-bold text-blue-600">{{ mb_strtoupper(mb_substr($p->name, 0, 1)) }}</span>
                <span class="truncate font-semibold text-slate-900">{{ $p->name }}</span>
                <svg x-show="sel == {{ $p->id }}" class="ml-auto h-4 w-4 shrink-0 text-blue-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0l-3.5-3.5a1 1 0 1 1 1.4-1.4l2.8 2.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
            </button>
        @endforeach
    </div>
</div>
