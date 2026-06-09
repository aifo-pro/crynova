@extends('layouts.app')
@section('title', 'Налаштування сайту')

@section('content')
<div>
    <div>
        <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
            <x-icon name="settings" class="h-3.5 w-3.5" />
            Адмін-панель
        </div>
        <h1 class="mt-4 text-3xl font-black tracking-[-0.03em] text-slate-950">Налаштування сайту</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500">Керування параметрами платформи, SMTP, комісіями, лімітами та безпекою.</p>
    </div>

    @if(session('success'))
        <x-alert variant="success" class="mt-6">{{ session('success') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}" style="margin-top: 32px;">
        @csrf

        <div class="space-y-7">
            @foreach($schema as $groupKey => $group)
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/70 px-6 py-5">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-blue-50 text-blue-700">
                            <x-icon :name="$group['icon']" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-lg font-black text-slate-950">{{ $group['label'] }}</h2>
                            <p class="mt-0.5 text-sm text-slate-500">{{ $group['hint'] ?? 'Platform-wide configuration group.' }}</p>
                        </div>
                    </div>

                    <div class="grid gap-5 p-6 sm:grid-cols-2">
                        @foreach($group['fields'] as $key => $field)
                            <div class="{{ ($field['type'] === 'bool' || ($field['wide'] ?? false)) ? 'sm:col-span-2' : '' }}">
                                @if($field['type'] === 'bool')
                                    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <span class="text-sm font-semibold text-slate-700">{{ $field['label'] }}</span>
                                        <span class="flex items-center gap-3">
                                            <input type="hidden" name="{{ $key }}" value="0">
                                            <input type="checkbox" name="{{ $key }}" value="1" @checked((string)($values[$key] ?? $field['default'] ?? '') === '1') class="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                        </span>
                                    </label>
                                    @if(isset($field['help']))
                                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $field['help'] }}</p>
                                    @endif
                                @else
                                    <label class="fin-label">{{ $field['label'] }}</label>
                                    @if($field['type'] === 'select')
                                        <select name="{{ $key }}" class="fin-input">
                                            @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                                                <option value="{{ $optionValue }}" @selected((string)($values[$key] ?? $field['default'] ?? '') === (string)$optionValue)>{{ $optionLabel }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($field['type'] === 'textarea')
                                        <textarea name="{{ $key }}" rows="{{ $field['rows'] ?? 4 }}" class="fin-input font-mono text-sm leading-6" placeholder="{{ $field['placeholder'] ?? '' }}">{{ $values[$key] ?? '' }}</textarea>
                                    @else
                                        <input
                                            name="{{ $key }}"
                                            type="{{ $field['type'] === 'number' ? 'number' : ($field['type'] === 'password' ? 'password' : ($field['type'] === 'email' ? 'email' : ($field['type'] === 'url' ? 'url' : 'text'))) }}"
                                            @isset($field['step']) step="{{ $field['step'] }}" @endisset
                                            value="{{ $values[$key] ?? '' }}"
                                            class="fin-input"
                                            placeholder="{{ $field['placeholder'] ?? '' }}"
                                            autocomplete="{{ $field['type'] === 'password' ? 'new-password' : 'off' }}"
                                        >
                                    @endif
                                    @if(isset($field['help']))
                                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $field['help'] }}</p>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="mt-7 flex justify-end">
            <x-button type="submit" icon="save" class="rounded-full px-8">Зберегти налаштування</x-button>
        </div>
    </form>
</div>
@endsection
