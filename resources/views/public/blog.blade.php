@extends('layouts.app')
@section('title', 'Blog')
@section('meta_description', 'Блог Crynova: гайди з приймання криптоплатежів, інтеграції API, безпеки та розвитку крипто-бізнесу.')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
    <div class="max-w-3xl">
        <x-badge variant="teal">Blog</x-badge>
        <h1 class="mt-5 text-4xl font-semibold text-white sm:text-5xl">Crypto payments, risk and product notes</h1>
        <p class="mt-5 text-lg leading-8 text-slate-300">Guides and operational thinking for merchants accepting digital asset payments.</p>
    </div>

    <div class="mt-10 grid gap-4 md:grid-cols-3">
        @forelse($posts as $post)
            <a href="{{ route('blog.show', $post->slug) }}" class="rounded-lg border border-slate-800 bg-slate-950/72 p-5 transition hover:border-teal-400/50 hover:bg-slate-900/80">
                @if($post->cover_image)
                    <img src="{{ $post->cover_image }}" alt="" class="mb-5 aspect-video w-full rounded-lg object-cover">
                @endif
                <p class="text-xs font-semibold text-teal-200">{{ $post->published_at?->format('M d, Y') }}</p>
                <h2 class="mt-3 text-lg font-semibold text-white">{{ $post->title }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-400">{{ $post->excerpt ?: str($post->body)->stripTags()->limit(130) }}</p>
            </a>
        @empty
            @foreach([
                ['How confirmations shape checkout UX', 'A practical look at pending states, finality and customer trust.'],
                ['Choosing stablecoin rails for merchants', 'ERC-20, TRC-20 and BEP-20 tradeoffs for payment teams.'],
                ['Webhook reliability in payment systems', 'Retries, signatures and event reconciliation patterns.'],
            ] as [$title, $text])
                <x-card>
                    <x-badge variant="slate">Draft preview</x-badge>
                    <h2 class="mt-4 text-lg font-semibold text-white">{{ $title }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-400">{{ $text }}</p>
                </x-card>
            @endforeach
        @endforelse
    </div>

    @if(method_exists($posts, 'links'))
        <div class="mt-8">{{ $posts->links() }}</div>
    @endif
</section>
@endsection
