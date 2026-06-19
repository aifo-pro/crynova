@extends('layouts.app')
@section('title', 'API Keys')

@section('content')
@php
    $permissions = [
        'invoices.create' => 'Create invoices',
        'invoices.read' => 'Read invoices and statuses',
        'invoices.cancel' => 'Expire unpaid invoices',
        'currencies.read' => 'Read supported currencies',
        'balance.read' => 'Read balances',
        'statistics.read' => 'Read statistics',
        'withdrawals.read' => 'Read withdrawals',
        'withdrawals.create' => 'Request withdrawals',
        'wallets.read' => 'Read static wallets',
        'wallets.create' => 'Create static wallets',
    ];
@endphp

<div class="space-y-6">
    <div>
        <x-badge variant="blue">Developer tools</x-badge>
        <h1 class="mt-3 text-3xl font-semibold text-slate-950">API keys</h1>
        <p class="mt-1 text-slate-500">Generate and revoke credentials for invoice API integrations.</p>
    </div>

    @if(session('new_api_key'))
        <x-alert variant="success" title="New API key">
            Copy this key now. It will not be shown again.
            <code id="new-api-key" class="mt-3 block rounded-lg bg-white p-3 font-mono text-sm text-emerald-700 break-all">{{ session('new_api_key') }}</code>
            <x-button type="button" variant="secondary" data-copy-target="new-api-key" class="mt-3" icon="copy">Copy key</x-button>
        </x-alert>
    @endif

    <x-card title="Create new key" subtitle="Leave all permissions unchecked to create a full-access key.">
        <form method="POST" action="{{ route('merchant.api-keys.store', $merchant) }}" class="space-y-5">
            @csrf
            <input type="text" name="name" placeholder="Production API" required class="fin-input">
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($permissions as $value => $label)
                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <input type="checkbox" name="permissions[]" value="{{ $value }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <x-button type="submit" icon="key">Generate key</x-button>
        </form>
    </x-card>

    <x-card title="Existing keys">
        <div class="space-y-3">
            @forelse($keys as $key)
                <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-slate-950">{{ $key->name }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <code class="rounded-lg bg-slate-100 px-2 py-1 text-xs text-slate-500">{{ $key->key_prefix }}...</code>
                            <x-status-badge :status="$key->is_active ? 'active' : 'revoked'" />
                            @if($key->last_used_at)<span class="text-xs text-slate-500">Last used {{ $key->last_used_at->diffForHumans() }}</span>@endif
                        </div>
                        @if(!empty($key->permissions))
                            <p class="mt-2 text-xs text-slate-500">{{ implode(', ', $key->permissions) }}</p>
                        @else
                            <p class="mt-2 text-xs text-slate-500">Full access</p>
                        @endif
                    </div>
                    @if($key->is_active)
                        <form method="POST" action="{{ route('merchant.api-keys.revoke', [$merchant, $key]) }}">
                            @csrf @method('DELETE')
                            <x-button type="submit" variant="danger" onclick="return confirm('Revoke this key?')">Revoke</x-button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No API keys yet.</p>
            @endforelse
        </div>
    </x-card>
</div>
@endsection
