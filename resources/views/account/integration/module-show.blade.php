@extends('layouts.app')
@section('title', $module->tr('name'))

@section('content')
<div class="space-y-6">
    <a href="{{ route('account.integration.modules') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-blue-600">
        <x-icon name="arrow-left" class="h-4 w-4" /> {{ __('account.integration.modules_title') }}
    </a>

    <div class="grid gap-6 lg:grid-cols-[1.4fr_0.6fr] lg:items-start">
        {{-- Main --}}
        <div class="space-y-6">
            {{-- Photo --}}
            <div class="aspect-video w-full overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-blue-50 via-white to-cyan-50">
                @if($module->imageUrl())
                    <img src="{{ $module->imageUrl() }}" alt="{{ $module->tr('name') }}" class="h-full w-full object-cover">
                @else
                    <span class="grid h-full w-full place-items-center text-blue-300">
                        <x-icon :name="$module->icon ?: 'layout'" class="h-16 w-16" />
                    </span>
                @endif
            </div>

            {{-- Title --}}
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-black tracking-[-0.02em] text-slate-950">{{ $module->tr('name') }}</h1>
                    @if($module->version)
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-bold text-blue-600">v{{ $module->version }}</span>
                    @endif
                </div>
                @if($module->tr('description'))
                    <p class="mt-3 text-lg leading-8 text-slate-600">{{ $module->tr('description') }}</p>
                @endif
            </div>

            {{-- Long description --}}
            @if($module->tr('long_description'))
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="prose-blog max-w-none whitespace-pre-line text-[15px] leading-7 text-slate-700">{{ $module->tr('long_description') }}</div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-4 lg:sticky lg:top-24">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">{{ __('account.integration.version') }}</dt>
                        <dd class="font-bold text-slate-950">{{ $module->version ? 'v'.$module->version : '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-t border-slate-100 pt-3">
                        <dt class="text-slate-500">{{ __('account.integration.format') }}</dt>
                        <dd class="font-bold text-slate-950">
                            @if($module->external_url)
                                {{ __('account.integration.external') }}
                            @elseif($module->file_path)
                                {{ strtoupper(pathinfo($module->file_path, PATHINFO_EXTENSION)) }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>

                @if($module->isDownloadable())
                    <a href="{{ route('account.integration.modules.download', $module) }}"
                       class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        {{ __('account.integration.download') }}
                    </a>
                @else
                    <p class="mt-5 rounded-xl bg-slate-50 px-4 py-3 text-center text-sm text-slate-400">{{ __('account.integration.soon') }}</p>
                @endif

                <a href="{{ route('api.sdk') }}" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-full border border-slate-200 px-6 py-3 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:text-blue-600">
                    <x-icon name="book" class="h-4 w-4" /> {{ __('account.integration.instructions') }}
                </a>
            </div>
        </aside>
    </div>
</div>
@endsection
