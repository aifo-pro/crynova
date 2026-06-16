@extends('layouts.app')

@section('title', $page->tr('meta_title') ?: $page->tr('title'))
@section('meta_description', $page->tr('meta_description') ?: \Illuminate\Support\Str::limit(strip_tags($page->tr('body')), 160))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
    <h1 class="text-3xl font-black tracking-[-0.02em] text-slate-950 sm:text-4xl">{{ $page->tr('title') }}</h1>
    <p class="mt-2 text-sm text-slate-400">{{ app()->getLocale() === 'uk' ? 'Оновлено' : (app()->getLocale() === 'pl' ? 'Zaktualizowano' : 'Updated') }}: {{ $page->updated_at->format('d.m.Y') }}</p>

    <div class="article-content mt-8">
        {!! \App\Support\SafeHtml::clean($page->tr('body')) !!}
    </div>
</div>
@endsection
