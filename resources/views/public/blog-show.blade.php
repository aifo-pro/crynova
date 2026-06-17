@extends('layouts.app')
@section('title', $post->metaTitle())
@section('meta_description', $post->metaDescription())
@section('og_type', 'article')
@if($post->cover_image)@section('og_image', $post->cover_image)@endif
@section('article_published', optional($post->published_at)->toIso8601String())
@section('article_modified', optional($post->updated_at)->toIso8601String())

@php
    $avg = $post->ratingAverage();
    $count = (int) $post->rating_count;
    $myRating = (int) request()->cookie('blog_rated_' . $post->id);
    $tgUrl = trim((string) \App\Models\Setting::get('telegram_support_url', '')) ?: trim((string) \App\Models\Setting::get('telegram_bot_url', ''));
    $shareUrl = urlencode(url()->current());
    $shareText = urlencode($post->tr('title'));
@endphp

@push('jsonld')
@php
    $ld = [
        '@context' => 'https://schema.org', '@type' => 'BlogPosting',
        'headline' => $post->tr('title'),
        'description' => $post->metaDescription(),
        'image' => $post->cover_image ?: asset('assets/crynova/logo-light.png'),
        'datePublished' => optional($post->published_at)->toIso8601String(),
        'dateModified' => optional($post->updated_at)->toIso8601String(),
        'author' => ['@type' => 'Organization', 'name' => 'Crynova'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Crynova', 'logo' => ['@type' => 'ImageObject', 'url' => asset('assets/crynova/logo-light.png')]],
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => url()->current()],
    ];
    if ($count > 0) {
        $ld['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => $avg, 'reviewCount' => $count, 'bestRating' => 5, 'worstRating' => 1];
    }
@endphp
<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
<div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
    <nav class="text-sm">
        <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 font-semibold text-slate-500 hover:text-blue-600">
            <x-icon name="arrow-left" class="h-4 w-4" /> {{ __('public.blog_page.back') }}
        </a>
    </nav>

    <div class="mt-6 grid gap-10 lg:grid-cols-[minmax(0,1fr)_18rem]">
        {{-- Article --}}
        <article>
            <header>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-500">
                    <span class="font-semibold text-blue-600">{{ optional($post->published_at)->translatedFormat('d MMMM Y') }}</span>
                    <span class="inline-flex items-center gap-1.5"><x-icon name="clock" class="h-4 w-4" /> {{ $post->readingMinutes() }} {{ __('public.blog_page.read_time') }}</span>
                    @if($count > 0)
                        <span class="inline-flex items-center gap-1 font-semibold text-amber-500">★ {{ $avg }} <span class="font-normal text-slate-400">({{ $count }})</span></span>
                    @endif
                </div>
                <h1 class="mt-3 text-3xl font-black tracking-[-0.02em] text-slate-950 sm:text-4xl">{{ $post->tr('title') }}</h1>
                @if($post->tr('excerpt'))<p class="mt-4 text-lg leading-8 text-slate-600">{{ $post->tr('excerpt') }}</p>@endif
                <div class="mt-5 flex items-center gap-3 border-y border-slate-100 py-4">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-blue-600 text-sm font-black text-white">C</span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ __('public.blog_page.author') }}</p>
                        <p class="text-xs text-slate-400">{{ optional($post->published_at)->translatedFormat('d MMMM Y') }}</p>
                    </div>
                </div>
            </header>

            @if($post->cover_image)
                <img src="{{ $post->cover_image }}" alt="{{ $post->tr('title') }}" class="mt-8 aspect-video w-full rounded-3xl border border-slate-200 object-cover">
            @endif

            <div id="article-content" class="article-content prose-blog mt-8">
                {!! \Illuminate\Support\Str::contains($post->tr('body'), '<') ? \App\Support\SafeHtml::clean($post->tr('body')) : nl2br(e($post->tr('body'))) !!}
            </div>

            {{-- Tags --}}
            @if(is_array($post->tags) && count($post->tags))
                <div class="mt-8 flex flex-wrap gap-2">
                    @foreach($post->tags as $tag)
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">#{{ $tag }}</span>
                    @endforeach
                </div>
            @endif

            {{-- Rating --}}
            <div class="mt-10 rounded-3xl border border-slate-200 bg-white p-6 text-center shadow-sm">
                <p class="text-lg font-bold text-slate-950">{{ __('public.blog_page.rate_title') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('public.blog_page.rate_hint') }}</p>
                @if($myRating)
                    <div class="mt-4 text-2xl text-amber-400">{!! str_repeat('★', $myRating) . str_repeat('☆', 5 - $myRating) !!}</div>
                    <p class="mt-2 text-sm font-semibold text-emerald-600">{{ __('public.blog_page.rated_thanks') }}</p>
                @else
                    <form method="POST" action="{{ route('blog.rate', $post->slug) }}" x-data="{ hover: 0 }" class="mt-4">
                        @csrf
                        <div class="flex items-center justify-center gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="submit" name="rating" value="{{ $i }}"
                                        @mouseenter="hover = {{ $i }}" @mouseleave="hover = 0"
                                        class="text-3xl transition" :class="hover >= {{ $i }} ? 'text-amber-400 scale-110' : 'text-slate-300'">★</button>
                            @endfor
                        </div>
                    </form>
                @endif
                <p class="mt-3 text-xs text-slate-400">
                    {{ $count > 0 ? __('public.blog_page.based_on', ['count' => $count]) : __('public.blog_page.no_ratings') }}
                </p>
            </div>

            {{-- CTA --}}
            <div class="mt-8 rounded-3xl border border-slate-200 bg-gradient-to-br from-blue-50 via-white to-cyan-50 p-6 text-center sm:p-8">
                <p class="text-lg font-bold text-slate-950">{{ __('public.blog_page.cta_title') }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ __('public.blog_page.cta_text') }}</p>
                <a href="{{ route('register') }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700">
                    {{ __('public.blog_page.cta_button') }} <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>
        </article>

        {{-- Sidebar --}}
        <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
            {{-- Table of contents --}}
            <div id="toc-box" class="hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('public.blog_page.contents') }}</p>
                <nav id="toc" class="space-y-1.5 text-sm"></nav>
            </div>

            {{-- Share --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" x-data="{ copied: false }">
                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('public.blog_page.share') }}</p>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="https://t.me/share/url?url={{ $shareUrl }}&text={{ $shareText }}" target="_blank" rel="noopener" aria-label="Telegram"
                       class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 text-slate-500 transition hover:border-blue-200 hover:text-blue-500">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M21.94 4.3 18.9 19.1c-.23 1.02-.84 1.27-1.7.79l-4.7-3.46-2.27 2.18c-.25.25-.46.46-.94.46l.34-4.78 8.7-7.86c.38-.34-.08-.53-.59-.19L6.97 13.2l-4.64-1.45c-1.01-.32-1.03-1.01.21-1.49l18.14-7c.84-.31 1.58.2 1.26 1.04Z"/></svg>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}" target="_blank" rel="noopener" aria-label="X"
                       class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 text-slate-500 transition hover:border-slate-900 hover:text-slate-900">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M18.9 2H22l-7.5 8.6L23 22h-6.8l-5.3-6.9L4.8 22H1.7l8-9.2L1 2h7l4.8 6.3L18.9 2Zm-2.4 18h1.9L7.1 4H5.1l11.4 16Z"/></svg>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener" aria-label="Facebook"
                       class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 text-slate-500 transition hover:border-blue-200 hover:text-blue-600">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M13.5 21.9V14h2.7l.4-3.1h-3.1V8.9c0-.9.25-1.5 1.55-1.5h1.65V4.6c-.3 0-1.3-.1-2.45-.1-2.4 0-4.05 1.5-4.05 4.15V10.9H7.7V14h2.45v7.9a10 10 0 1 0 3.35 0Z"/></svg>
                    </a>
                    <button type="button" @click="navigator.clipboard.writeText('{{ url()->current() }}'); copied = true; setTimeout(() => copied = false, 1800)"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-semibold text-slate-500 transition hover:border-blue-200 hover:text-blue-600">
                        <x-icon name="copy" class="h-4 w-4" />
                        <span x-text="copied ? '{{ __('public.blog_page.link_copied') }}' : '{{ __('public.blog_page.copy_link') }}'"></span>
                    </button>
                </div>
            </div>

            {{-- Telegram block --}}
            @if($tgUrl)
                <div class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-cyan-50 p-5 text-center shadow-sm">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-blue-600 text-white">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M21.94 4.3 18.9 19.1c-.23 1.02-.84 1.27-1.7.79l-4.7-3.46-2.27 2.18c-.25.25-.46.46-.94.46l.34-4.78 8.7-7.86c.38-.34-.08-.53-.59-.19L6.97 13.2l-4.64-1.45c-1.01-.32-1.03-1.01.21-1.49l18.14-7c.84-.31 1.58.2 1.26 1.04Z"/></svg>
                    </span>
                    <p class="mt-3 font-bold text-slate-950">{{ __('public.blog_page.tg_title') }}</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ __('public.blog_page.tg_text') }}</p>
                    <a href="{{ $tgUrl }}" target="_blank" rel="noopener" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-blue-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-700">
                        {{ __('public.blog_page.tg_button') }} <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                </div>
            @endif
        </aside>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const content = document.getElementById('article-content');
    const toc = document.getElementById('toc');
    const box = document.getElementById('toc-box');
    if (!content || !toc) return;
    const heads = content.querySelectorAll('h2, h3');
    if (heads.length < 2) return;
    box.classList.remove('hidden');
    heads.forEach((h, i) => {
        const id = 'sec-' + i;
        h.id = id;
        const a = document.createElement('a');
        a.href = '#' + id;
        a.textContent = h.textContent;
        a.className = 'block rounded-lg px-2 py-1 text-slate-500 hover:bg-slate-50 hover:text-blue-600 ' + (h.tagName === 'H3' ? 'pl-5 text-[13px]' : 'font-medium');
        a.addEventListener('click', e => { e.preventDefault(); h.scrollIntoView({ behavior: 'smooth', block: 'start' }); history.replaceState(null, '', '#' + id); });
        toc.appendChild(a);
    });
});
</script>
@endsection
