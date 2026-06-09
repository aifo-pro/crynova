@extends('layouts.app')
@section('title', 'Webhook Settings')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-white">Webhook settings</h1>
        <p class="mt-1 text-slate-400">Receive signed invoice lifecycle events in your backend.</p>
    </div>

    @if(session('new_webhook_secret'))
    <x-alert variant="success" title="Signing secret — copy it now">
        This secret will not be shown again. Store it securely to verify webhook signatures.
        <code id="webhook-secret" class="mt-3 block rounded-lg bg-black/60 p-3 font-mono text-sm text-emerald-200 break-all">{{ session('new_webhook_secret') }}</code>
        <x-button type="button" variant="secondary" data-copy-target="webhook-secret" class="mt-3" icon="copy">Copy secret</x-button>
    </x-alert>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1fr_0.75fr]">
        <x-card title="Endpoint configuration" subtitle="Events are sent as POST with HMAC-SHA256 signature.">
            <form method="POST" action="{{ route('merchant.webhooks.save') }}" class="space-y-5">
                @csrf
                <div>
                    <label class="fin-label">Endpoint URL</label>
                    <input name="url" type="url" required class="fin-input"
                           value="{{ $webhook?->url }}"
                           placeholder="https://your-app.com/webhooks/crynova">
                </div>
                <div>
                    <label class="fin-label">Events to receive</label>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach($availableEvents as $event)
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-800 bg-slate-900/50 px-3 py-2">
                            <input type="checkbox" name="events[]" value="{{ $event }}"
                                   class="rounded border-slate-700 bg-slate-900 text-teal-400"
                                   @checked(!$webhook || in_array($event, $webhook->events ?? $availableEvents))>
                            <span class="font-mono text-xs text-slate-300">{{ $event }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-button type="submit" icon="link">{{ $webhook ? 'Update endpoint' : 'Save endpoint' }}</x-button>
                    @if($webhook)
                    <form method="POST" action="{{ route('merchant.webhooks.regenerate-secret') }}" class="inline">
                        @csrf
                        <x-button type="submit" variant="secondary" icon="key">Regenerate secret</x-button>
                    </form>
                    @endif
                </div>
            </form>
        </x-card>

        <x-card title="Signature verification">
            <pre class="overflow-x-auto rounded-lg bg-black/60 p-4 text-xs text-slate-200"><code>$sig = hash_hmac('sha256', $rawBody, $secret);
if (!hash_equals($sig, $header)) {
    abort(401);
}</code></pre>
            <p class="mt-3 text-xs text-slate-500">Header: <code class="text-teal-200">X-Crynova-Sig: sha256=...</code></p>
        </x-card>
    </div>

    <x-card title="Delivery history">
        <x-table :headers="['Event', 'Invoice', 'Status', 'HTTP', 'Attempt', 'Time']">
            @forelse($logs as $log)
            <tr class="hover:bg-slate-900/60">
                <td class="px-4 py-3 font-mono text-xs">{{ $log->event }}</td>
                <td class="px-4 py-3 font-mono text-xs text-slate-400">{{ $log->invoice ? substr($log->invoice->uuid, 0, 8).'…' : '—' }}</td>
                <td class="px-4 py-3"><span class="text-xs {{ $log->success ? 'text-teal-300' : 'text-rose-300' }}">{{ $log->success ? '✓ Delivered' : '✗ Failed' }}</span></td>
                <td class="px-4 py-3 text-xs text-slate-400">{{ $log->http_status ?? '—' }}</td>
                <td class="px-4 py-3 text-xs text-slate-400">{{ $log->attempt }}</td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ $log->created_at->diffForHumans() }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No deliveries yet.</td></tr>
            @endforelse
        </x-table>
        <div class="mt-4">{{ $logs->links() }}</div>
    </x-card>
</div>
@endsection
