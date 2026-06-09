@extends('layouts.app')
@section('title', 'Security')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-950">Security</h1>
        <p class="mt-1 text-slate-500">Two-factor authentication and password.</p>
    </div>
    {{-- 2FA --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="mb-3 font-semibold text-slate-950">Two-factor authentication</p>
        @if($user->google2fa_enabled)
        <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
            <x-icon name="shield-check" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
            <div>
                <p class="font-semibold text-emerald-700">2FA is enabled</p>
                <p class="mt-0.5 text-sm text-slate-500">Your account is protected with a TOTP authenticator.</p>
            </div>
        </div>
        <x-button href="{{ route('2fa.setup') }}" variant="secondary" icon="settings" class="mt-4">Manage 2FA</x-button>
        @else
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <x-icon name="alert-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
            <div>
                <p class="font-semibold text-amber-700">2FA is not enabled</p>
                <p class="mt-0.5 text-sm text-slate-500">Strongly recommended to protect withdrawals and API keys.</p>
            </div>
        </div>
        <x-button href="{{ route('2fa.setup') }}" icon="shield" class="mt-4">Enable 2FA</x-button>
        @endif
    </div>

    {{-- Password --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="mb-3 font-semibold text-slate-950">Change password</p>
        <form method="POST" action="{{ route('account.password') }}" class="space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="fin-label">Current password</label>
                <input name="current_password" type="password" autocomplete="current-password" required
                       class="fin-input @error('current_password') border-rose-500 @enderror">
                @error('current_password')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="fin-label">New password</label>
                <input name="password" type="password" autocomplete="new-password" required
                       class="fin-input @error('password') border-rose-500 @enderror">
                @error('password')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="fin-label">Confirm new password</label>
                <input name="password_confirmation" type="password" autocomplete="new-password" required class="fin-input">
            </div>
            <x-button type="submit" variant="secondary" icon="lock">Update password</x-button>
        </form>
    </div>

    {{-- Account activity --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="mb-3 font-semibold text-slate-950">Account activity</p>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Last login</dt><dd class="text-slate-800">{{ $user->last_login_at?->diffForHumans() ?? 'Unknown' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Last IP</dt><dd class="font-mono text-slate-800">{{ $user->last_login_ip ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Member since</dt><dd class="text-slate-800">{{ $user->created_at->format('d M Y') }}</dd></div>
        </dl>
    </div>
</div>
@endsection
