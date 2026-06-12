@extends('layouts.app')
@section('title', $item->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($item->excerpt ?: $item->body), 160))
@section('og_type', 'article')
@if($item->cover_image)@section('og_image', $item->cover_image)@endif
@section('article_published', optional($item->published_at)->toIso8601String())
@section('article_modified', optional($item->updated_at)->toIso8601String())

@push('jsonld')
@php
    $ldPost = json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'NewsArticle',
        'headline' => $item->title,
        'description' => \Illuminate\Support\Str::limit(strip_tags($item->excerpt ?: $item->body), 200),
        'image'    => $item->cover_image ?: asset('assets/crynova/logo-light.png'),
        'datePublished' => optional($item->published_at)->toIso8601String(),
        'dateModified'  => optional($item->updated_at)->toIso8601String(),
        'author'    => ['@type' => 'Organization', 'name' => 'Crynova'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Crynova', 'logo' => ['@type' => 'ImageObject', 'url' => asset('assets/crynova/logo-light.png')]],
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => url()->current()],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $ldCrumb = json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('public.news_page.title'), 'item' => route('news')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $item->title, 'item' => url()->current()],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp
<script type="application/ld+json">{!! $ldPost !!}</script>
<script type="application/ld+json">{!! $ldCrumb !!}</script>
@endpush

@section('content')
<article class="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:px-8">
    <nav class="text-sm text-slate-400">
        <a href="{{ route('news') }}" class="inline-flex items-center gap-2 font-semibold text-slate-500 hover:text-blue-600">
            <x-icon name="arrow-left" class="h-4 w-4" /> {{ __('public.news_page.back') }}
        </a>
    </nav>

    <header class="mt-8">
        <p class="text-sm font-semibold text-blue-600">{{ optional($item->published_at)->translatedFormat('d MMMM Y') }}</p>
        <h1 class="mt-3 text-3xl font-black tracking-[-0.02em] text-slate-950 sm:text-4xl">{{ $item->title }}</h1>
        @if($item->excerpt)<p class="mt-4 text-lg leading-8 text-slate-600">{{ $item->excerpt }}</p>@endif
    </header>

    @if($item->cover_image)
        <img src="{{ $item->cover_image }}" alt="{{ $item->title }}" class="mt-8 aspect-video w-full rounded-3xl border border-slate-200 object-cover">
    @endif

    <div class="article-content mt-8">
        {!! \Illuminate\Support\Str::contains($item->body, '<') ? \App\Support\SafeHtml::clean($item->body) : nl2br(e($item->body)) !!}
    </div>
</article>
@endsection
