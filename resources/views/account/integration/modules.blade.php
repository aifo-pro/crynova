@extends('layouts.app')
@section('title', __('account.integration.modules_title'))

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-950">{{ __('account.integration.modules_title') }}</h1>
        <p class="mt-1 text-slate-500">{{ __('account.integration.modules_text') }}</p>
    </div>

    @if($modules->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                <x-icon name="layout" class="h-6 w-6" />
            </span>
            <p class="mt-4 font-semibold text-slate-700">{{ __('account.integration.modules_empty') }}</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($modules as $mod)
                <div class="flex flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600"><x-icon :name="$mod->icon ?: 'layout'" class="h-5 w-5" /></span>
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-950">{{ $mod->name }}</p>
                            @if($mod->version)<span class="text-xs text-slate-400">v{{ $mod->version }}</span>@endif
                            @if($mod->description)<p class="mt-0.5 text-xs text-slate-500">{{ $mod->description }}</p>@endif
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('account.integration.modules.download', $mod) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                            <x-icon name="arrow-right" class="h-3.5 w-3.5" /> {{ __('account.integration.download') }}
                        </a>
                        <a href="{{ route('developers') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            <x-icon name="book" class="h-3.5 w-3.5" /> {{ __('account.integration.instructions') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
