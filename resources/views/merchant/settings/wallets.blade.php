@extends('layouts.app')
@section('title', __('merchant_settings.wallets.title', ['name' => $merchant->name]))

@section('content')
<div class="space-y-6" x-data="{ showCreate: false }">
    @include('merchant.settings._tabs')
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="mb-4 flex items-center gap-2">
            <x-icon name="wallet" class="h-5 w-5 text-blue-600" />
            <h2 class="text-lg font-semibold text-slate-950">{{ __('merchant_settings.wallets.heading') }}</h2>
        </div>

        {{-- Toolbar --}}
        <form method="GET" class="mb-5 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <input name="search" value="{{ request('search') }}" class="fin-input pl-9" placeholder="{{ __('merchant_settings.wallets.search') }}">
                <x-icon name="globe" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-300" />
            </div>
            <select name="currency" class="fin-input w-40">
                <option value="">{{ __('merchant_settings.wallets.currency') }}</option>
                @foreach($currencies as $c)<option value="{{ $c->id }}" @selected(request('currency')==$c->id)>{{ $c->code }}</option>@endforeach
            </select>
            <select name="status" class="fin-input w-36">
                <option value="">{{ __('merchant_settings.wallets.status') }}</option>
                <option value="active" @selected(request('status')==='active')>{{ __('merchant_settings.wallets.active') }}</option>
                <option value="disabled" @selected(request('status')==='disabled')>{{ __('merchant_settings.wallets.disabled') }}</option>
            </select>
            <x-button type="submit" variant="secondary">{{ __('merchant_settings.wallets.filter') }}</x-button>
            <button type="button" @click="showCreate=!showCreate" class="ml-auto rounded-full border border-blue-600 px-5 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50">{{ __('merchant_settings.wallets.create') }}</button>
        </form>

        {{-- Create form --}}
        <div x-show="showCreate" x-cloak class="mb-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <form method="POST" action="{{ route('merchant.settings.wallets.store', $merchant) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="flex-1 min-w-40">
                    <label class="fin-label">{{ __('merchant_settings.wallets.currency') }}</label>
                    <select name="currency_id" required class="fin-input">
                        <option value="">{{ __('merchant_settings.wallets.choose') }}</option>
                        @foreach($currencies as $c)<option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-40">
                    <label class="fin-label">{{ __('merchant_settings.wallets.client_id') }} <span class="text-slate-400">({{ __('merchant_settings.wallets.optional') }})</span></label>
                    <input name="client_identifier" type="text" class="fin-input" placeholder="client-123">
                </div>
                <x-button type="submit" icon="plus">{{ __('merchant_settings.common.create') }}</x-button>
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-3 py-3">{{ __('merchant_settings.wallets.currency') }}</th>
                        <th class="px-3 py-3">{{ __('merchant_settings.wallets.address') }}</th>
                        <th class="px-3 py-3">UUID</th>
                        <th class="px-3 py-3">{{ __('merchant_settings.wallets.client_id') }}</th>
                        <th class="px-3 py-3">{{ __('merchant_settings.wallets.created_at') }}</th>
                        <th class="px-3 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($wallets as $w)
                    <tr class="border-b border-slate-50 hover:bg-slate-50/60">
                        <td class="px-3 py-3 font-semibold text-slate-950">{{ $w->currency->code }}</td>
                        <td class="px-3 py-3 font-mono text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($w->address, 20) }}</td>
                        <td class="px-3 py-3 font-mono text-xs text-slate-400">{{ substr($w->uuid, 0, 8) }}…</td>
                        <td class="px-3 py-3 text-slate-600">{{ $w->client_identifier ?? '—' }}</td>
                        <td class="px-3 py-3 text-xs text-slate-400">{{ $w->created_at->format('d.m.Y H:i') }}</td>
                        <td class="px-3 py-3 text-right">
                            <form method="POST" action="{{ route('merchant.settings.wallets.destroy', [$merchant, $w]) }}" onsubmit="return confirm('{{ __('merchant_settings.wallets.delete_confirm') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-rose-500 hover:underline">{{ __('merchant_settings.wallets.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-3 py-12 text-center text-slate-400">
                        {{ __('merchant_settings.wallets.empty') }}<br>
                        <span class="text-sm">{{ __('merchant_settings.wallets.empty_hint') }}</span>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($wallets->hasPages())<div class="mt-4">{{ $wallets->links() }}</div>@endif
    </div>
</div>
@endsection
