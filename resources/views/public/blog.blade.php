@extends('layouts.app')
@section('title', __('public.blog_page.title'))
@section('meta_description', __('public.blog_page.meta'))

@push('jsonld')
@php
    $ldBlog = json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'Blog',
        'name'     => __('public.blog_page.title'),
        'url'      => route('blog'),
        'description' => __('public.blog_page.meta'),
        'publisher' => ['@type' => 'Organization', 'name' => 'Crynova'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp
<script type="application/ld+json">{!! $ldBlog !!}</script>
@endpush

@section('content')
<section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
    <div class="max-w-3xl">
        <span class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-blue-700">{{ __('public.blog_page.badge') }}</span>
        <h1 class="mt-5 text-4xl font-black tracking-[-0.02em] text-slate-950 sm:text-5xl">{{ __('public.blog_page.heading') }}</h1>
        <p class="mt-5 text-lg leading-8 text-slate-600">{{ __('public.blog_page.subtitle') }}</p>
    </div>

    @if($posts->isEmpty())
        <div class="mt-12 rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-400">
            {{ __('public.blog_page.empty') }}
        </div>
    @else
        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($posts as $post)
                <a href="{{ route('blog.show', $post->slug) }}" class="group flex flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-xl">
                    @if($post->cover_image)
                        <img src="{{ $post->cover_image }}" alt="{{ $post->tr('title') }}" class="aspect-video w-full object-cover" loading="lazy">
                    @else
                        <div class="aspect-video w-full bg-gradient-to-br from-blue-50 via-white to-cyan-50"></div>
                    @endif
                    <div class="flex flex-1 flex-col p-5">
                        <p class="text-xs font-semibold text-blue-600">{{ optional($post->published_at)->translatedFormat('d MMMM Y') }}</p>
                        <h2 class="mt-2 text-lg font-bold leading-snug text-slate-950 group-hover:text-blue-700">{{ $post->tr('title') }}</h2>
                        <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-500">{{ $post->tr('excerpt') ?: \Illuminate\Support\Str::limit(strip_tags($post->tr('body')), 130) }}</p>
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-blue-600">{{ __('public.blog_page.read') }} <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" /></span>
                    </div>
                </a>
            @endforeach
        </div>

        @if(method_exists($posts, 'links'))
            <div class="mt-10">{{ $posts->links() }}</div>
        @endif
    @endif
</section>
@endsection
