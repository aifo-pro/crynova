@extends('layouts.app')

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($page->body), 160))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
    <h1 class="text-3xl font-black tracking-[-0.02em] text-slate-950 sm:text-4xl">{{ $page->title }}</h1>
    <p class="mt-2 text-sm text-slate-400">{{ app()->getLocale() === 'uk' ? 'Оновлено' : 'Updated' }}: {{ $page->updated_at->format('d.m.Y') }}</p>

    <div class="prose prose-slate mt-8 max-w-none prose-headings:font-bold prose-headings:text-slate-950 prose-a:text-blue-600 prose-p:leading-7 prose-li:leading-7">
        {!! $page->body !!}
    </div>
</div>
@endsection
