@extends('layouts.app')
@section('title', 'База знаний')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-950">База знаний</h1>
        <p class="mt-1 text-slate-500">Відповіді на найчастіші питання щодо роботи з платформою.</p>
    </div>

    @foreach($sections as $section)
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center gap-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><x-icon :name="$section['icon']" class="h-5 w-5" /></span>
            <h2 class="text-lg font-semibold text-slate-950">{{ $section['title'] }}</h2>
        </div>
        <div class="space-y-2">
            @foreach($section['articles'] as $i => $a)
            <div x-data="{ open: false }" class="rounded-2xl border border-slate-200">
                <button type="button" @click="open=!open" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left text-sm font-medium text-slate-800">
                    {{ $a['q'] }}
                    <x-icon name="chevron-down" class="h-4 w-4 shrink-0 text-slate-400 transition" ::class="open ? 'rotate-180' : ''" />
                </button>
                <div x-show="open" x-cloak class="border-t border-slate-100 px-4 py-3 text-sm text-slate-500">{{ $a['a'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endsection
