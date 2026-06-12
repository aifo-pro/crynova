@extends('layouts.app')
@section('title', $post->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?: $post->body), 160))
@section('og_type', 'article')
@if($post->cover_image)@section('og_image', $post->cover_image)@endif
@section('article_published', optional($post->published_at)->toIso8601String())
@section('article_modified', optional($post->updated_at)->toIso8601String())

@push('jsonld')
@php
    $ldPost = json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'BlogPosting',
        'headline' => $post->title,
        'description' => \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?: $post->body), 200),
        'image'    => $post->cover_image ?: asset('assets/crynova/logo-light.png'),
        'datePublished' => optional($post->published_at)->toIso8601String(),
        'dateModified'  => optional($post->updated_at)->toIso8601String(),
        'author'    => ['@type' => 'Organization', 'name' => 'Crynova'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Crynova', 'logo' => ['@type' => 'ImageObject', 'url' => asset('assets/crynova/logo-light.png')]],
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => url()->current()],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $ldCrumb = json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('public.blog_page.title'), 'item' => route('blog')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $post->title, 'item' => url()->current()],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp
<script type="application/ld+json">{!! $ldPost !!}</script>
<script type="application/ld+json">{!! $ldCrumb !!}</script>
@endpush

@section('content')
<article class="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:px-8">
    <nav class="text-sm text-slate-400">
        <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 font-semibold text-slate-500 hover:text-blue-600">
            <x-icon name="arrow-left" class="h-4 w-4" /> {{ __('public.blog_page.back') }}
        </a>
    </nav>

    <header class="mt-8">
        <p class="text-sm font-semibold text-blue-600">{{ optional($post->published_at)->translatedFormat('d MMMM Y') }}</p>
        <h1 class="mt-3 text-3xl font-black tracking-[-0.02em] text-slate-950 sm:text-4xl">{{ $post->title }}</h1>
        @if($post->excerpt)<p class="mt-4 text-lg leading-8 text-slate-600">{{ $post->excerpt }}</p>@endif
    </header>

    @if($post->cover_image)
        <img src="{{ $post->cover_image }}" alt="{{ $post->title }}" class="mt-8 aspect-video w-full rounded-3xl border border-slate-200 object-cover">
    @endif

    <div class="article-content mt-8">
        {!! \Illuminate\Support\Str::contains($post->body, '<') ? \App\Support\SafeHtml::clean($post->body) : nl2br(e($post->body)) !!}
    </div>

    <div class="mt-12 rounded-3xl border border-slate-200 bg-gradient-to-br from-blue-50 via-white to-cyan-50 p-6 text-center sm:p-8">
        <p class="text-lg font-bold text-slate-950">{{ __('public.blog_page.cta_title') }}</p>
        <p class="mt-1 text-sm text-slate-600">{{ __('public.blog_page.cta_text') }}</p>
        <a href="{{ route('register') }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700">
            {{ __('public.blog_page.cta_button') }} <x-icon name="arrow-right" class="h-4 w-4" />
        </a>
    </div>
</article>
@endsection
