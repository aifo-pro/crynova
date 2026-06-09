@extends('layouts.app')
@section('title', 'Payment Links')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-950 dark:text-white">Payment links</h1>
            <p class="mt-1 text-slate-500">Create reusable crypto payment URLs — share them via email, social, QR or embed on your site.</p>
        </div>
    </div>
    {{-- ── Create link form ──────────────────────────────────────────── --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
        <p class="mb-4 font-semibold text-slate-950 dark:text-white">Create new link</p>
        <form method="POST" action="{{ route('merchant.payment-links.store') }}"
              class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @csrf
            <div>
                <label class="fin-label">Title <span class="text-slate-400">(optional)</span></label>
                <input name="title" type="text" class="fin-input" placeholder="VIP membership" value="{{ old('title') }}">
            </div>
            <div>
                <label class="fin-label">Amount <span class="text-slate-400">(0 = customer enters)</span></label>
                <input name="amount" type="number" step="any" min="0" class="fin-input" placeholder="0.00" value="{{ old('amount', 0) }}">
            </div>
            <div>
                <label class="fin-label">Currency</label>
                <select name="currency_id" class="fin-input">
                    <option value="">— customer chooses —</option>
                    @foreach($currencies as $c)
                        <option value="{{ $c->id }}" @selected(old('currency_id') == $c->id)>{{ $c->code }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="fin-label">Max uses <span class="text-slate-400">(0 = unlimited)</span></label>
                <input name="max_uses" type="number" min="0" class="fin-input" placeholder="0" value="{{ old('max_uses', 0) }}">
            </div>
            <div class="sm:col-span-2">
                <label class="fin-label">Description <span class="text-slate-400">(shown on checkout)</span></label>
                <input name="description" type="text" class="fin-input" placeholder="Payment for annual subscription" value="{{ old('description') }}">
            </div>
            <div class="sm:col-span-2">
                <label class="fin-label">Success redirect URL <span class="text-slate-400">(optional)</span></label>
                <input name="success_url" type="url" class="fin-input" placeholder="https://example.com/thank-you" value="{{ old('success_url') }}">
            </div>
            <div class="sm:col-span-2 lg:col-span-4">
                <x-button type="submit" icon="link">Generate link</x-button>
            </div>
        </form>
    </div>

    {{-- ── Links list ────────────────────────────────────────────────── --}}
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <p class="font-semibold text-slate-950 dark:text-white">Your payment links <span class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500 dark:bg-slate-800">{{ $links->total() }}</span></p>
        </div>

        @forelse($links as $link)
        @php $url = $link->getPublicUrl(); @endphp
        <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 last:border-0 dark:border-slate-800 sm:flex-row sm:items-center">
            {{-- Status dot + info --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full {{ $link->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                    <p class="font-semibold text-slate-950 dark:text-white">{{ $link->title ?: 'Untitled link' }}</p>
                    @if($link->currency)
                        <x-badge>{{ $link->currency->code }}</x-badge>
                    @else
                        <x-badge variant="slate">Any currency</x-badge>
                    @endif
                </div>
                @if($link->description)
                    <p class="mt-0.5 text-xs text-slate-400">{{ $link->description }}</p>
                @endif
                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-400">
                    <span>
                        Amount: <strong class="text-slate-700 dark:text-slate-300">
                            {{ $link->amount ? $link->amount : 'Variable' }}
                        </strong>
                    </span>
                    <span>
                        Uses: <strong class="text-slate-700 dark:text-slate-300">
                            {{ $link->use_count }}{{ $link->max_uses ? ' / '.$link->max_uses : '' }}
                        </strong>
                    </span>
                    <span>Created {{ $link->created_at->diffForHumans() }}</span>
                </div>
                <div class="mt-2 flex items-center gap-2">
                    <code id="link-{{ $link->id }}" class="truncate max-w-xs rounded-lg bg-slate-50 px-2 py-1 text-xs font-mono text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $url }}</code>
                    <button type="button" data-copy-target="link-{{ $link->id }}"
                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-500 hover:text-blue-600 dark:border-slate-700 dark:bg-slate-900">
                        <x-icon name="copy" class="h-3 w-3" /> Copy
                    </button>
                </div>
            </div>

            {{-- QR --}}
            <div class="shrink-0">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ urlencode($url) }}"
                     alt="QR" class="rounded-xl border border-slate-200 dark:border-slate-700" width="80" height="80">
            </div>

            {{-- Actions --}}
            <div class="flex shrink-0 flex-wrap gap-2">
                <a href="{{ $url }}" target="_blank"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-blue-200 hover:text-blue-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                    <x-icon name="globe" class="h-3 w-3" /> Preview
                </a>
                <form method="POST" action="{{ route('merchant.payment-links.toggle', $link) }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-semibold transition
                            {{ $link->is_active
                                ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-300'
                                : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-300' }}">
                        {{ $link->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('merchant.payment-links.destroy', $link) }}"
                      onsubmit="return confirm('Delete this link?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-300">
                        <x-icon name="trash" class="h-3 w-3" /> Delete
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="px-6 py-12 text-center text-slate-400">
            <x-icon name="link" class="mx-auto mb-3 h-8 w-8 opacity-30" />
            <p>No payment links yet. Create one above.</p>
        </div>
        @endforelse

        @if($links->hasPages())
        <div class="border-t border-slate-100 px-6 py-4 dark:border-slate-800">
            {{ $links->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
