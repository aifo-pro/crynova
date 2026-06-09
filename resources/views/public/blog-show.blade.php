@extends('layouts.app')
@section('title', $post->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?: $post->body), 160))

@push('jsonld')
@php
    $ldPost = json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'BlogPosting',
        'headline' => $post->title,
        'description' => \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?: $post->body), 200),
        'datePublished' => optional($post->published_at)->toIso8601String(),
        'dateModified'  => optional($post->updated_at)->toIso8601String(),
        'author'    => ['@type' => 'Organization', 'name' => 'Crynova'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Crynova', 'logo' => ['@type' => 'ImageObject', 'url' => asset('assets/crynova/logo-light.png')]],
        'mainEntityOfPage' => url()->current(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp
<script type="application/ld+json">{!! $ldPost !!}</script>
@endpush

@section('content')
<article class="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:px-8">
    <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-white">
        <x-icon name="arrow-left" class="h-4 w-4" /> Back to blog
    </a>
    <p class="mt-8 text-sm font-semibold text-teal-200">{{ $post->published_at?->format('M d, Y') }}</p>
    <h1 class="mt-3 text-4xl font-semibold text-white">{{ $post->title }}</h1>
    @if($post->excerpt)<p class="mt-5 text-lg leading-8 text-slate-300">{{ $post->excerpt }}</p>@endif
    @if($post->cover_image)<img src="{{ $post->cover_image }}" alt="" class="mt-8 aspect-video w-full rounded-lg object-cover">@endif
    <div class="mt-8 space-y-5 text-base leading-8 text-slate-300">
        {!! nl2br(e($post->body)) !!}
    </div>
</article>
@endsection
