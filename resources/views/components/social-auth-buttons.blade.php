@php
    $googleEnabled = (bool) \App\Models\Setting::get('google_auth_enabled', false)
        && trim((string) \App\Models\Setting::get('google_client_id', '')) !== ''
        && trim((string) \App\Models\Setting::get('google_client_secret', '')) !== '';

    $telegramUsername = ltrim(trim((string) \App\Models\Setting::get('telegram_login_bot_username', '')), '@');
    $telegramEnabled = (bool) \App\Models\Setting::get('telegram_auth_enabled', false)
        && $telegramUsername !== ''
        && trim((string) \App\Models\Setting::get('telegram_login_bot_token', '')) !== '';
@endphp

@if($googleEnabled || $telegramEnabled)
    <div {{ $attributes->merge(['class' => 'space-y-4']) }}>
        <div class="flex items-center gap-4 text-sm text-slate-500">
            <span class="h-px flex-1 bg-slate-100"></span>
            <span>Або продовжити через</span>
            <span class="h-px flex-1 bg-slate-100"></span>
        </div>

        <div class="grid gap-3 {{ $googleEnabled && $telegramEnabled ? 'sm:grid-cols-2' : '' }}">
            @if($googleEnabled)
                <a href="{{ route('auth.google.redirect') }}" class="flex h-12 items-center justify-center gap-3 rounded-full border border-slate-200 bg-white px-5 text-sm font-bold text-slate-800 shadow-sm transition hover:border-blue-200 hover:bg-blue-50">
                    <span class="grid h-6 w-6 place-items-center rounded-full bg-white text-base font-black text-blue-600">G</span>
                    Google
                </a>
            @endif

            @if($telegramEnabled)
                <div class="flex h-12 items-center justify-center rounded-full border border-slate-200 bg-white px-3 shadow-sm">
                    <script async src="https://telegram.org/js/telegram-widget.js?22"
                        data-telegram-login="{{ $telegramUsername }}"
                        data-size="large"
                        data-radius="999"
                        data-auth-url="{{ route('auth.telegram.callback') }}"
                        data-request-access="write"></script>
                </div>
            @endif
        </div>
    </div>
@endif
