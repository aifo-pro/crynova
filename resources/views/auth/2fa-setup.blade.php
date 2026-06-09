@extends('layouts.app')
@section('title', 'Set Up Two-Factor Authentication')

@section('content')
<section class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-12">
    <div class="w-full max-w-lg space-y-6">
        <div class="text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl border border-teal-400/30 bg-teal-400/10 text-teal-200">
                <x-icon name="shield-check" class="h-7 w-7" />
            </div>
            <h1 class="text-2xl font-semibold text-white">Set up two-factor authentication</h1>
            <p class="mt-2 text-sm text-slate-400">Scan the QR code with your authenticator app, then confirm with a code.</p>
        </div>

        {{-- Step 1 – scan QR --}}
        <x-card title="Step 1 — Scan QR code">
            <div class="flex flex-col items-center gap-5 sm:flex-row sm:items-start">
                {{-- QR rendered via Google Charts / qrserver --}}
                <div class="shrink-0 overflow-hidden rounded-xl border border-slate-700 bg-white p-2">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ urlencode($qrUrl) }}"
                         alt="2FA QR code"
                         class="h-40 w-40"
                         loading="lazy">
                </div>
                <div class="space-y-3">
                    <p class="text-sm text-slate-400">
                        Open <strong class="text-slate-200">Google Authenticator</strong>,
                        <strong class="text-slate-200">Aegis</strong>,
                        or any TOTP-compatible app, then scan this code.
                    </p>
                    <div>
                        <p class="mb-1 text-xs text-slate-500">Or enter the key manually:</p>
                        <div class="flex items-center gap-2 rounded-lg border border-slate-800 bg-black/40 px-3 py-2">
                            <code id="totp-secret" class="flex-1 break-all font-mono text-xs text-teal-200">{{ $secret }}</code>
                            <button type="button" data-copy-target="totp-secret"
                                    class="shrink-0 rounded p-1 text-slate-400 hover:text-white transition-colors"
                                    title="Copy key">
                                <x-icon name="copy" class="h-4 w-4" />
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Type: Time-based (TOTP) · Digits: 6 · Period: 30s</p>
                    </div>
                </div>
            </div>
        </x-card>

        {{-- Step 2 – confirm code --}}
        <x-card title="Step 2 — Confirm setup">
            <form method="POST" action="{{ route('2fa.setup') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="fin-label" for="code">6-digit code from your app</label>
                    <input id="code" name="code" type="text" inputmode="numeric"
                           maxlength="6" autocomplete="one-time-code" autofocus
                           class="fin-input text-center font-mono text-xl tracking-[0.4em] @error('code') border-rose-500 @enderror"
                           placeholder="· · · · · ·">
                    @error('code')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-button type="submit" icon="shield-check">Enable 2FA</x-button>
                    <x-button href="{{ route('account.security') }}" variant="ghost">Cancel</x-button>
                </div>
            </form>
        </x-card>

        {{-- If already enabled — disable section --}}
        @if(auth()->user()->google2fa_enabled)
        <x-card title="Disable 2FA" subtitle="You will need your current password to disable 2FA.">
            <form method="POST" action="{{ route('2fa.disable') }}" class="flex flex-wrap gap-3">
                @csrf
                @method('DELETE')
                <input name="password" type="password" class="fin-input flex-1" placeholder="Current password" required autocomplete="current-password">
                <x-button type="submit" variant="danger" icon="shield-off">Disable 2FA</x-button>
            </form>
        </x-card>
        @endif
    </div>
</section>
@endsection
