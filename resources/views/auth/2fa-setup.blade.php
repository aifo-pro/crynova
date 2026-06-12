@extends('layouts.app')
@section('title', __('auth.tfa.page_title'))

@section('content')
<section class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-12">
    <div class="w-full max-w-lg space-y-6">
        <div class="text-center">
            <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-600/25">
                <x-icon name="shield-check" class="h-7 w-7" />
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-950">{{ $enabled ? __('auth.tfa.manage_heading') : __('auth.tfa.heading') }}</h1>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">{{ $enabled ? __('auth.tfa.manage_subtitle') : __('auth.tfa.subtitle') }}</p>
        </div>

        @if($enabled)
        {{-- Already enabled — status --}}
        <div class="flex items-start gap-3 rounded-3xl border border-emerald-200 bg-emerald-50 p-5">
            <x-icon name="shield-check" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
            <div>
                <p class="font-semibold text-emerald-700">{{ __('account.security.tfa_on') }}</p>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('account.security.tfa_on_text') }}</p>
            </div>
        </div>
        @else
        {{-- Step 1 – scan QR --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-2">
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-blue-50 text-xs font-black text-blue-600">1</span>
                <h2 class="font-semibold text-slate-950">{{ __('auth.tfa.step1') }}</h2>
            </div>
            <div class="flex flex-col items-center gap-5 sm:flex-row sm:items-start">
                <div class="shrink-0 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=176x176&data={{ urlencode($qrUrl) }}"
                         alt="2FA QR code" class="h-44 w-44" loading="lazy">
                </div>
                <div class="space-y-3">
                    <p class="text-sm leading-6 text-slate-600">
                        {!! __('auth.tfa.open_app', [
                            'a1' => '<strong class="font-semibold text-slate-900">Google Authenticator</strong>',
                            'a2' => '<strong class="font-semibold text-slate-900">Aegis</strong>',
                        ]) !!}
                    </p>
                    <div>
                        <p class="mb-1.5 text-xs font-medium text-slate-500">{{ __('auth.tfa.manual') }}</p>
                        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                            <code id="totp-secret" class="flex-1 break-all font-mono text-xs font-semibold text-blue-600">{{ $secret }}</code>
                            <button type="button" data-copy-target="totp-secret"
                                    class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-white hover:text-blue-600"
                                    title="{{ __('auth.tfa.copy_key') }}">
                                <x-icon name="copy" class="h-4 w-4" />
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">{{ __('auth.tfa.meta') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2 – confirm code --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-2">
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-blue-50 text-xs font-black text-blue-600">2</span>
                <h2 class="font-semibold text-slate-950">{{ __('auth.tfa.step2') }}</h2>
            </div>
            <form method="POST" action="{{ route('2fa.setup') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="fin-label" for="code">{{ __('auth.tfa.code_label') }}</label>
                    <input id="code" name="code" type="text" inputmode="numeric"
                           maxlength="6" autocomplete="one-time-code" autofocus
                           class="fin-input text-center font-mono text-xl tracking-[0.4em] @error('code') border-rose-500 @enderror"
                           placeholder="· · · · · ·">
                    @error('code')
                    <p class="mt-1.5 text-xs font-medium text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                @unless($hasRecovery)
                <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                    <label class="fin-label" for="recovery_word">{{ __('auth.tfa.recovery_label') }}</label>
                    <input id="recovery_word" name="recovery_word" type="text" value="{{ old('recovery_word') }}"
                           minlength="4" maxlength="64" required
                           class="fin-input @error('recovery_word') border-rose-500 @enderror"
                           placeholder="{{ __('auth.tfa.recovery_placeholder') }}">
                    <p class="mt-2 flex items-start gap-2 text-xs leading-5 text-slate-500">
                        <x-icon name="alert-triangle" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-500" />
                        {{ __('auth.tfa.recovery_hint') }}
                    </p>
                    @error('recovery_word')<p class="mt-1.5 text-xs font-medium text-rose-500">{{ $message }}</p>@enderror
                </div>
                @endunless

                <div class="flex flex-wrap items-center gap-3">
                    <x-button type="submit" icon="shield-check">{{ __('auth.tfa.enable') }}</x-button>
                    <a href="{{ route('account.security') }}" class="text-sm font-semibold text-slate-500 transition hover:text-slate-900">{{ __('auth.tfa.cancel') }}</a>
                </div>
            </form>
        </div>
        @endif

        {{-- If already enabled — disable section --}}
        @if($enabled)
        <div class="rounded-3xl border border-rose-200 bg-rose-50/40 p-6">
            <h2 class="font-semibold text-slate-950">{{ __('auth.tfa.disable_title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('auth.tfa.disable_sub') }}</p>
            <form method="POST" action="{{ route('2fa.disable') }}" class="mt-4 flex flex-wrap gap-3">
                @csrf
                @method('DELETE')
                <input name="password" type="password" class="fin-input flex-1" placeholder="{{ __('auth.tfa.current_pass') }}" required autocomplete="current-password">
                <x-button type="submit" variant="danger" icon="shield-off">{{ __('auth.tfa.disable') }}</x-button>
            </form>
        </div>
        @endif
    </div>
</section>
@endsection
