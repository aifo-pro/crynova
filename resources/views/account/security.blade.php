@extends('layouts.app')
@section('title', __('account.security.title'))

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-950">{{ __('account.security.title') }}</h1>
        <p class="mt-1 text-slate-500">{{ __('account.security.subtitle') }}</p>
    </div>
    {{-- 2FA --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="mb-3 font-semibold text-slate-950">{{ __('account.security.tfa') }}</p>
        @if($user->google2fa_enabled)
        <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
            <x-icon name="shield-check" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
            <div>
                <p class="font-semibold text-emerald-700">{{ __('account.security.tfa_on') }}</p>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('account.security.tfa_on_text') }}</p>
            </div>
        </div>
        <x-button href="{{ route('2fa.setup') }}" variant="secondary" icon="settings" class="mt-4">{{ __('account.security.manage') }}</x-button>
        @else
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <x-icon name="alert-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
            <div>
                <p class="font-semibold text-amber-700">{{ __('account.security.tfa_off') }}</p>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('account.security.tfa_off_text') }}</p>
            </div>
        </div>
        <x-button href="{{ route('2fa.setup') }}" icon="shield" class="mt-4">{{ __('account.security.enable') }}</x-button>
        @endif
    </div>

    {{-- Password --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="mb-3 font-semibold text-slate-950">{{ __('account.security.change_password') }}</p>
        <form method="POST" action="{{ route('account.password') }}" class="space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="fin-label">{{ __('account.security.current') }}</label>
                <input name="current_password" type="password" autocomplete="current-password" required
                       class="fin-input @error('current_password') border-rose-500 @enderror">
                @error('current_password')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="fin-label">{{ __('account.security.new') }}</label>
                <input name="password" type="password" autocomplete="new-password" required
                       class="fin-input @error('password') border-rose-500 @enderror">
                @error('password')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="fin-label">{{ __('account.security.confirm') }}</label>
                <input name="password_confirmation" type="password" autocomplete="new-password" required class="fin-input">
            </div>
            <x-button type="submit" variant="secondary" icon="lock">{{ __('account.security.update') }}</x-button>
        </form>
    </div>

    {{-- Account activity --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="mb-3 font-semibold text-slate-950">{{ __('account.security.activity') }}</p>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('account.security.last_login') }}</dt><dd class="text-slate-800">{{ $user->last_login_at?->diffForHumans() ?? __('account.security.unknown') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('account.security.last_ip') }}</dt><dd class="font-mono text-slate-800">{{ $user->last_login_ip ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('account.security.member_since') }}</dt><dd class="text-slate-800">{{ $user->created_at->format('d M Y') }}</dd></div>
        </dl>
    </div>
</div>
@endsection
