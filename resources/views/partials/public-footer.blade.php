@php
    $registrationOpen = (bool) \App\Models\Setting::get('registration_enabled', true);
    $telegramSupportUrl = trim((string) \App\Models\Setting::get('telegram_support_url', ''));
    $youtubeUrl = trim((string) \App\Models\Setting::get('youtube_url', ''));
    $telegramBotUrl = trim((string) \App\Models\Setting::get('telegram_bot_url', ''));
@endphp

<footer class="border-t border-slate-200 bg-slate-50">
    <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-1">
                <x-logo class="h-10 w-auto" />
                <p class="mt-4 max-w-xs text-sm leading-6 text-slate-500">{{ __('public.footer.tagline') }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('public.footer.product') }}</p>
                <ul class="mt-4 space-y-2 text-sm text-slate-600">
                    <li><a href="{{ route('pricing') }}" class="hover:text-blue-600">{{ __('ui.pricing') }}</a></li>
                    <li><a href="{{ route('coins') }}" class="hover:text-blue-600">{{ __('ui.coins') }}</a></li>
                    <li><a href="{{ route('developers') }}" class="hover:text-blue-600">{{ __('ui.developers') }}</a></li>
                    <li><a href="{{ route('blog') }}" class="hover:text-blue-600">{{ __('ui.blog') }}</a></li>
                </ul>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('public.footer.company') }}</p>
                <ul class="mt-4 space-y-2 text-sm text-slate-600">
                    <li><a href="{{ route('contact') }}" class="hover:text-blue-600">{{ __('ui.contact') }}</a></li>
                    @if($registrationOpen)
                        <li><a href="{{ route('register') }}" class="hover:text-blue-600">{{ __('ui.sign_up') }}</a></li>
                    @endif
                    <li><a href="{{ route('login') }}" class="hover:text-blue-600">{{ __('ui.login') }}</a></li>
                    @if($telegramSupportUrl !== '')
                        <li><a href="{{ $telegramSupportUrl }}" target="_blank" rel="noopener" class="hover:text-blue-600">Telegram</a></li>
                    @endif
                    @if($youtubeUrl !== '')
                        <li><a href="{{ $youtubeUrl }}" target="_blank" rel="noopener" class="hover:text-blue-600">YouTube</a></li>
                    @endif
                    @if($telegramBotUrl !== '')
                        <li><a href="{{ $telegramBotUrl }}" target="_blank" rel="noopener" class="hover:text-blue-600">Telegram bot</a></li>
                    @endif
                </ul>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('public.footer.legal') }}</p>
                <ul class="mt-4 space-y-2 text-sm text-slate-600">
                    <li><a href="{{ route('legal.terms') }}" class="hover:text-blue-600">{{ __('public.footer.terms') }}</a></li>
                    <li><a href="{{ route('legal.privacy') }}" class="hover:text-blue-600">{{ __('public.footer.privacy') }}</a></li>
                    <li><a href="{{ route('legal.aml-kyc') }}" class="hover:text-blue-600">{{ __('public.footer.aml') }}</a></li>
                    <li><a href="{{ route('legal.risk-disclosure') }}" class="hover:text-blue-600">{{ __('public.footer.risk') }}</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-12 flex flex-col gap-3 border-t border-slate-200 pt-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} Crynova. {{ __('public.footer.rights') }}</p>
            <x-language-switcher />
        </div>
    </div>
</footer>
