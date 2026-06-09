@extends('layouts.app')
@section('title', __('account.settings.security_title'))

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-semibold text-slate-950">{{ __('account.settings.title') }}</h1>
    @include('account.settings._tabs')
    @if(session('new_account_key'))
        <x-alert variant="warning" title="{{ __('account.settings.api_key_created') }}">
            <code id="new-acc-key" class="mt-2 block break-all rounded-lg bg-black/5 p-2 font-mono text-sm">{{ session('new_account_key') }}</code>
            <x-button type="button" variant="secondary" data-copy-target="new-acc-key" class="mt-2" icon="copy">{{ __('account.settings.copy') }}</x-button>
        </x-alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2">
                <x-icon name="user" class="h-5 w-5 text-blue-600" />
                <h2 class="font-semibold text-slate-950">{{ __('account.settings.login_history') }}</h2>
            </div>

            <div class="space-y-2">
                @forelse($logins as $log)
                    <div class="flex items-center justify-between gap-4 text-sm">
                        <span class="text-slate-400">{{ $log->created_at->format('d.m.y H:i') }}</span>
                        <span class="font-mono text-xs text-slate-600">{{ $log->actor_ip ?? '—' }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">{{ __('account.settings.no_records') }}</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-3 flex items-center gap-2">
                    <x-icon name="shield" class="h-5 w-5 text-blue-600" />
                    <h2 class="font-semibold text-slate-950">
                        {{ __('account.settings.2fa') }}
                        <span class="text-sm font-normal {{ $user->google2fa_enabled ? 'text-emerald-600' : 'text-rose-500' }}">
                            {{ $user->google2fa_enabled ? __('account.settings.enabled') : __('account.settings.disabled') }}
                        </span>
                    </h2>
                </div>

                @if($user->google2fa_enabled)
                    <x-button href="{{ route('2fa.setup') }}" variant="secondary" icon="settings" class="w-full rounded-full">{{ __('account.settings.manage_2fa') }}</x-button>
                @else
                    <x-button href="{{ route('2fa.setup') }}" class="w-full rounded-full">{{ __('account.settings.enable_2fa') }}</x-button>
                @endif
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-3 flex items-center gap-2">
                    <x-icon name="key" class="h-5 w-5 text-blue-600" />
                    <h2 class="font-semibold text-slate-950">{{ __('account.settings.api_key') }}</h2>
                </div>

                <p class="mb-3 text-sm text-slate-500">{{ __('account.settings.api_key_text') }}</p>

                <div class="rounded-xl border border-slate-200 px-3 py-2.5">
                    <span class="font-mono text-sm text-slate-500">{{ $user->account_api_key ? str_repeat('•', 32) : __('account.settings.key_not_created') }}</span>
                </div>

                <form method="POST" action="{{ route('account.settings.security.api-key') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-blue-600 hover:underline">{{ __('account.settings.create_new') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
