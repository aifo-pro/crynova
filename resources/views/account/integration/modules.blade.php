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
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($modules as $mod)
                <a href="{{ route('account.integration.modules.show', $mod->slug) }}"
                   class="group flex flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:border-blue-200 hover:shadow-xl hover:shadow-slate-200">
                    {{-- Photo --}}
                    <div class="relative aspect-video w-full overflow-hidden bg-gradient-to-br from-blue-50 via-white to-cyan-50">
                        @if($mod->imageUrl())
                            <img src="{{ $mod->imageUrl() }}" alt="{{ $mod->name }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                        @else
                            <span class="grid h-full w-full place-items-center text-blue-300">
                                <x-icon :name="$mod->icon ?: 'layout'" class="h-12 w-12" />
                            </span>
                        @endif
                        @if($mod->version)
                            <span class="absolute right-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-xs font-bold text-slate-700 shadow-sm backdrop-blur">v{{ $mod->version }}</span>
                        @endif
                    </div>
                    {{-- Body --}}
                    <div class="flex flex-1 flex-col p-5">
                        <p class="text-base font-bold text-slate-950 transition group-hover:text-blue-700">{{ $mod->name }}</p>
                        @if($mod->description)
                            <p class="mt-1.5 line-clamp-2 flex-1 text-sm leading-6 text-slate-500">{{ $mod->description }}</p>
                        @endif
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-blue-600">
                            {{ __('account.integration.details') }} <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
