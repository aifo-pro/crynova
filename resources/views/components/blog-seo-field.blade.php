@props([
    'name',
    'label',
    'max' => 60,
    'value' => '',
    'type' => 'input',
    'placeholder' => '',
])

@php
    $len = mb_strlen((string) $value);
    // SEO sweet-spot: green inside the recommended band, amber near/over the limit.
    $lo = $type === 'textarea' ? 120 : 50;
@endphp

<div x-data="{ len: {{ $len }}, max: {{ $max }}, lo: {{ $lo }} }">
    <div class="flex items-center justify-between">
        <label class="fin-label">{{ $label }}</label>
        <span class="text-xs font-semibold tabular-nums"
              :class="len > max ? 'text-rose-500' : (len >= lo ? 'text-emerald-500' : 'text-slate-400')"
              x-text="len + ' / ' + max"></span>
    </div>
    @if($type === 'textarea')
        <textarea name="{{ $name }}" rows="3" maxlength="{{ $max + 20 }}" x-on:input="len = $event.target.value.length"
                  class="fin-input @error($name) border-rose-500 @enderror" placeholder="{{ $placeholder }}">{{ $value }}</textarea>
    @else
        <input name="{{ $name }}" type="text" maxlength="{{ $max + 15 }}" x-on:input="len = $event.target.value.length"
               value="{{ $value }}" class="fin-input @error($name) border-rose-500 @enderror" placeholder="{{ $placeholder }}">
    @endif
    @error($name)<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
    <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-slate-100">
        <div class="h-full rounded-full transition-all"
             :class="len > max ? 'bg-rose-400' : (len >= lo ? 'bg-emerald-400' : 'bg-blue-400')"
             :style="'width:' + Math.min(100, (len / max) * 100) + '%'"></div>
    </div>
</div>
