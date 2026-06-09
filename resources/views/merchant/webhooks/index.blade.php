@extends('layouts.app')
@section('title', __('merchant.webhooks.title'))

@section('content')
@php
    $selectedEvents = $webhook?->events ?: $availableEvents;
    $logItems = $logs->getCollection();
    $deliveredCount = $logItems->where('success', true)->count();
    $failedCount = $logItems->where('success', false)->count();
    $lastDelivery = $logItems->first()?->created_at?->diffForHumans();
    $isConfigured = filled($webhook?->url);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <x-badge variant="blue">{{ __('merchant.webhooks.badge') }}</x-badge>
            <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950">{{ __('merchant.webhooks.title') }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ __('merchant.webhooks.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-button href="{{ route('merchant.docs.index', $merchant) }}" variant="secondary" icon="book">{{ __('merchant.webhooks.open_docs') }}</x-button>
        </div>
    </div>

    @if(session('new_webhook_secret'))
        <x-alert variant="success" :title="__('merchant.webhooks.secret_title')" class="rounded-2xl">
            <p>{{ __('merchant.webhooks.secret_text') }}</p>
            <code id="webhook-secret" class="mt-3 block rounded-2xl border border-emerald-200 bg-white px-4 py-3 font-mono text-sm text-emerald-700 break-all">{{ session('new_webhook_secret') }}</code>
            <x-button type="button" variant="secondary" data-copy-target="webhook-secret" class="mt-3" icon="copy">{{ __('merchant.webhooks.copy_secret') }}</x-button>
        </x-alert>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ __('merchant.webhooks.status') }}</p>
                    <p class="mt-2 text-2xl font-black text-slate-950">{{ $isConfigured ? __('merchant.webhooks.configured') : __('merchant.webhooks.not_configured') }}</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $isConfigured ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">
                    <x-icon :name="$isConfigured ? 'check' : 'clock'" class="h-5 w-5" />
                </span>
            </div>
        </div>
        <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ __('merchant.webhooks.events_enabled') }}</p>
                    <p class="mt-2 text-2xl font-black text-slate-950">{{ count($selectedEvents) }} / {{ count($availableEvents) }}</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                    <x-icon name="bell" class="h-5 w-5" />
                </span>
            </div>
        </div>
        <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ __('merchant.webhooks.last_delivery') }}</p>
                    <p class="mt-2 text-2xl font-black text-slate-950">{{ $lastDelivery ?? '—' }}</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-600">
                    <x-icon name="link" class="h-5 w-5" />
                </span>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <section class="rounded-[1.75rem] border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-950">{{ __('merchant.webhooks.endpoint_title') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('merchant.webhooks.endpoint_subtitle') }}</p>
                </div>
                @if($isConfigured)
                    <x-badge variant="green">{{ __('merchant.webhooks.active') }}</x-badge>
                @else
                    <x-badge variant="yellow">{{ __('merchant.webhooks.setup_required') }}</x-badge>
                @endif
            </div>

            <form method="POST" action="{{ route('merchant.webhooks.save', $merchant) }}" class="space-y-6 p-5">
                @csrf
                <div>
                    <label class="fin-label" for="webhook-url">{{ __('merchant.webhooks.endpoint_url') }}</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            <x-icon name="link" class="h-4 w-4" />
                        </span>
                        <input id="webhook-url" name="url" type="url" required class="fin-input min-h-14 pl-11"
                               value="{{ old('url', $webhook?->url) }}"
                               placeholder="https://your-app.com/webhooks/crynova">
                    </div>
                    @error('url')<p class="mt-2 text-xs font-medium text-rose-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <label class="fin-label mb-0">{{ __('merchant.webhooks.events_title') }}</label>
                        <span class="text-xs font-semibold text-slate-400">{{ __('merchant.webhooks.events_hint') }}</span>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach($availableEvents as $event)
                            @php($eventKey = str_replace('.', '_', $event))
                            <label class="group flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-blue-200 hover:bg-blue-50/50">
                                <input type="checkbox" name="events[]" value="{{ $event }}"
                                       class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                       @checked(in_array($event, $selectedEvents, true))>
                                <span class="min-w-0">
                                    <span class="block font-mono text-xs font-black text-slate-950">{{ $event }}</span>
                                    <span class="mt-1 block text-xs leading-5 text-slate-500">{{ __('merchant.webhooks.events.'.$eventKey) }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('events')<p class="mt-2 text-xs font-medium text-rose-500">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <x-button type="submit" icon="link">{{ $webhook ? __('merchant.webhooks.update_endpoint') : __('merchant.webhooks.save_endpoint') }}</x-button>
                    <p class="text-xs leading-5 text-slate-500">{{ __('merchant.webhooks.secret_note') }}</p>
                </div>
            </form>

            @if($webhook)
                <form method="POST" action="{{ route('merchant.webhooks.regenerate-secret', $merchant) }}" class="border-t border-slate-200 px-5 py-4">
                    @csrf
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-black text-slate-950">{{ __('merchant.webhooks.rotate_title') }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ __('merchant.webhooks.rotate_text') }}</p>
                        </div>
                        <x-button type="submit" variant="secondary" icon="key">{{ __('merchant.webhooks.regenerate_secret') }}</x-button>
                    </div>
                </form>
            @endif
        </section>

        <aside class="space-y-5">
            <section class="rounded-[1.75rem] border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-black text-slate-950">{{ __('merchant.webhooks.signature_title') }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ __('merchant.webhooks.signature_subtitle') }}</p>
                        </div>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                            <x-icon name="shield-check" class="h-5 w-5" />
                        </span>
                    </div>
                </div>
                <div class="p-5">
                    <pre class="overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs leading-6 text-slate-100 shadow-inner"><code>$sig = hash_hmac('sha256', $rawBody, $secret);
if (! hash_equals($sig, $header)) {
    abort(401);
}</code></pre>
                    <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('merchant.webhooks.header_label') }}</p>
                        <code class="mt-1 block font-mono text-xs font-bold text-blue-700 break-all">X-Crynova-Sig: sha256=...</code>
                    </div>
                </div>
            </section>

            <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-lg shadow-slate-200/50">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-black text-slate-950">{{ __('merchant.webhooks.delivery_quality') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ __('merchant.webhooks.delivery_quality_text') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-black text-emerald-600">{{ $deliveredCount }}</p>
                        <p class="text-xs font-semibold text-slate-400">{{ __('merchant.webhooks.delivered') }}</p>
                    </div>
                </div>
                @if($failedCount > 0)
                    <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
                        {{ trans_choice('merchant.webhooks.failed_count', $failedCount, ['count' => $failedCount]) }}
                    </div>
                @endif
            </section>
        </aside>
    </div>

    <section class="rounded-[1.75rem] border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-950">{{ __('merchant.webhooks.history_title') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('merchant.webhooks.history_subtitle') }}</p>
            </div>
            <x-badge variant="slate">{{ __('merchant.webhooks.attempts_on_page', ['count' => $logItems->count()]) }}</x-badge>
        </div>

        <div class="p-5">
            <x-table :headers="[__('merchant.webhooks.table.event'), __('merchant.webhooks.table.invoice'), __('merchant.webhooks.table.status'), __('merchant.webhooks.table.http'), __('merchant.webhooks.table.attempt'), __('merchant.webhooks.table.time')]">
                @forelse($logs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-4">
                            <code class="rounded-lg bg-slate-100 px-2 py-1 font-mono text-xs font-bold text-slate-700">{{ $log->event }}</code>
                        </td>
                        <td class="px-4 py-4 font-mono text-xs text-slate-500">{{ $log->invoice ? substr($log->invoice->uuid, 0, 8).'…' : '—' }}</td>
                        <td class="px-4 py-4">
                            <x-badge :variant="$log->success ? 'green' : 'red'">
                                {{ $log->success ? __('merchant.webhooks.delivered') : __('merchant.webhooks.failed') }}
                            </x-badge>
                        </td>
                        <td class="px-4 py-4 font-mono text-xs text-slate-500">{{ $log->http_status ?? '—' }}</td>
                        <td class="px-4 py-4 text-sm font-semibold text-slate-700">{{ $log->attempt }}</td>
                        <td class="px-4 py-4 text-sm text-slate-500">{{ $log->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12">
                            <div class="mx-auto max-w-sm text-center">
                                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                    <x-icon name="bell" class="h-5 w-5" />
                                </span>
                                <p class="mt-4 font-black text-slate-950">{{ __('merchant.webhooks.empty_title') }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ __('merchant.webhooks.empty_text') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>
            <div class="mt-4">{{ $logs->links() }}</div>
        </div>
    </section>
</div>
@endsection
