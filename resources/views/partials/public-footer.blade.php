@php
    $registrationOpen = (bool) \App\Models\Setting::get('registration_enabled', true);
    $telegramSupportUrl = trim((string) \App\Models\Setting::get('telegram_support_url', ''));
    $instagramUrl = trim((string) \App\Models\Setting::get('instagram_url', ''));
    $youtubeUrl = trim((string) \App\Models\Setting::get('youtube_url', ''));
    $telegramBotUrl = trim((string) \App\Models\Setting::get('telegram_bot_url', ''));
    $hasSocials = $telegramSupportUrl || $instagramUrl || $youtubeUrl;
@endphp

<footer class="border-t border-slate-200 bg-gradient-to-b from-white to-slate-50">
    <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <div class="grid gap-12 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
            {{-- Brand --}}
            <div>
                <x-logo class="h-10 w-auto" />
                <p class="mt-4 max-w-xs text-sm leading-6 text-slate-500">{{ __('public.footer.tagline') }}</p>

                @if($hasSocials)
                    <div class="mt-6 flex items-center gap-2.5">
                        @if($telegramSupportUrl)
                            <a href="{{ $telegramSupportUrl }}" target="_blank" rel="noopener" aria-label="Telegram"
                               class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:text-blue-500 hover:shadow-md">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M21.94 4.3 18.9 19.1c-.23 1.02-.84 1.27-1.7.79l-4.7-3.46-2.27 2.18c-.25.25-.46.46-.94.46l.34-4.78 8.7-7.86c.38-.34-.08-.53-.59-.19L6.97 13.2l-4.64-1.45c-1.01-.32-1.03-1.01.21-1.49l18.14-7c.84-.31 1.58.2 1.26 1.04Z"/></svg>
                            </a>
                        @endif
                        @if($instagramUrl)
                            <a href="{{ $instagramUrl }}" target="_blank" rel="noopener" aria-label="Instagram"
                               class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:-translate-y-0.5 hover:border-pink-200 hover:text-pink-500 hover:shadow-md">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                            </a>
                        @endif
                        @if($youtubeUrl)
                            <a href="{{ $youtubeUrl }}" target="_blank" rel="noopener" aria-label="YouTube"
                               class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:-translate-y-0.5 hover:border-rose-200 hover:text-rose-500 hover:shadow-md">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M23 12s0-3.3-.42-4.88a2.55 2.55 0 0 0-1.8-1.8C19.2 5 12 5 12 5s-7.2 0-8.78.32a2.55 2.55 0 0 0-1.8 1.8C1 8.7 1 12 1 12s0 3.3.42 4.88c.23.86.9 1.53 1.8 1.76C4.8 19 12 19 12 19s7.2 0 8.78-.36a2.55 2.55 0 0 0 1.8-1.76C23 15.3 23 12 23 12ZM9.75 15.02v-6.04L15.5 12l-5.75 3.02Z"/></svg>
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Product --}}
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('public.footer.product') }}</p>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-600">
                    <li><a href="{{ route('pricing') }}" class="transition hover:text-blue-600">{{ __('ui.pricing') }}</a></li>
                    <li><a href="{{ route('coins') }}" class="transition hover:text-blue-600">{{ __('ui.coins') }}</a></li>
                    <li><a href="{{ route('developers') }}" class="transition hover:text-blue-600">{{ __('ui.developers') }}</a></li>
                    <li><a href="{{ route('api.docs') }}" class="transition hover:text-blue-600">API</a></li>
                    <li><a href="{{ route('blog') }}" class="transition hover:text-blue-600">{{ __('ui.blog') }}</a></li>
                </ul>
            </div>

            {{-- Company --}}
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('public.footer.company') }}</p>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-600">
                    <li><a href="{{ route('contact') }}" class="transition hover:text-blue-600">{{ __('ui.contact') }}</a></li>
                    @if($registrationOpen)
                        <li><a href="{{ route('register') }}" class="transition hover:text-blue-600">{{ __('ui.sign_up') }}</a></li>
                    @endif
                    <li><a href="{{ route('login') }}" class="transition hover:text-blue-600">{{ __('ui.login') }}</a></li>
                    @if($telegramSupportUrl)
                        <li><a href="{{ $telegramSupportUrl }}" target="_blank" rel="noopener" class="transition hover:text-blue-600">Telegram</a></li>
                    @endif
                    @if($telegramBotUrl)
                        <li><a href="{{ $telegramBotUrl }}" target="_blank" rel="noopener" class="transition hover:text-blue-600">Telegram bot</a></li>
                    @endif
                </ul>
            </div>

            {{-- Legal --}}
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('public.footer.legal') }}</p>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-600">
                    <li><a href="{{ url('/tos') }}" class="transition hover:text-blue-600">{{ __('public.footer.terms') }}</a></li>
                    <li><a href="{{ url('/privacy') }}" class="transition hover:text-blue-600">{{ __('public.footer.privacy') }}</a></li>
                    <li><a href="{{ url('/aml-kyc') }}" class="transition hover:text-blue-600">{{ __('public.footer.aml') }}</a></li>
                    <li><a href="{{ url('/risk') }}" class="transition hover:text-blue-600">{{ __('public.footer.risk') }}</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-14 flex flex-col gap-4 border-t border-slate-200 pt-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} Crynova. {{ __('public.footer.rights') }}</p>
            <div class="flex items-center gap-4">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span> {{ __('public.footer.status') }}
                </span>
                <x-language-switcher compact />
            </div>
        </div>
    </div>
</footer>
