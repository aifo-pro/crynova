<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    // ── SEO context ───────────────────────────────────────────────
    $siteNameSetting = (string) \App\Models\Setting::get('site_name', 'Crynova');
    $siteDescriptionSetting = (string) \App\Models\Setting::get('site_description', __('public.home.subtitle'));
    $siteUrlSetting = trim((string) \App\Models\Setting::get('site_url', url('/'))) ?: url('/');
    $sameAs = array_values(array_filter([
        \App\Models\Setting::get('telegram_support_url', ''),
        \App\Models\Setting::get('youtube_url', ''),
        \App\Models\Setting::get('telegram_bot_url', ''),
    ]));
    $seoRoute   = optional(request()->route())->getName() ?? '';
    $seoPrivate = \Illuminate\Support\Str::startsWith($seoRoute, ['account.', 'merchant.', 'admin.', '2fa.', 'checkout.', 'verification.'])
        || in_array($seoRoute, ['login', 'register', 'password.request', 'logout'], true);
    $seoTitle   = trim($__env->yieldContent('title', $siteNameSetting));
    $seoFullTitle = $seoTitle === $siteNameSetting ? $siteNameSetting.' — '.__('public.home.title') : $seoTitle.' — '.$siteNameSetting;
    $seoDesc    = trim($__env->yieldContent('meta_description', $siteDescriptionSetting));
    $seoCanonical = url()->current();
    $seoImage   = asset('assets/crynova/logo-light.png');
    $seoLocale  = app()->getLocale() === 'uk' ? 'uk_UA' : 'en_US';
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2563eb">

    @php
        $googleSiteVerification = trim((string) \App\Models\Setting::get('google_site_verification', ''));
        // Tolerate pasting the full <meta ... content="CODE"> tag — extract just the code.
        if (preg_match('/content=["\']([^"\']+)["\']/i', $googleSiteVerification, $m)) {
            $googleSiteVerification = $m[1];
        }
    @endphp
    @if($googleSiteVerification !== '')
        <meta name="google-site-verification" content="{{ $googleSiteVerification }}">
    @endif

    {{-- Primary SEO --}}
    <title>{{ $seoFullTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}">
    <link rel="canonical" href="{{ $seoCanonical }}">
    @if($seoPrivate)
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
        <link rel="alternate" hreflang="uk" href="{{ $seoCanonical }}">
        <link rel="alternate" hreflang="en" href="{{ $seoCanonical }}">
        <link rel="alternate" hreflang="x-default" href="{{ $seoCanonical }}">
    @endif

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteNameSetting }}">
    <meta property="og:title" content="{{ $seoFullTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:locale" content="{{ $seoLocale }}">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoFullTitle }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    <meta name="twitter:image" content="{{ $seoImage }}">

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    <link rel="mask-icon" href="{{ asset('favicon.svg') }}" color="#2563eb">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    {{-- Organization + WebSite structured data (public pages) --}}
    @unless($seoPrivate)
    @php
        $ldOrg = json_encode([
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => $siteNameSetting,
            'url'      => $siteUrlSetting,
            'logo'     => $seoImage,
            'description' => $seoDesc,
            'sameAs' => $sameAs,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $ldSite = json_encode([
            '@context' => 'https://schema.org',
            '@type'    => 'WebSite',
            'name'     => $siteNameSetting,
            'url'      => $siteUrlSetting,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    <script type="application/ld+json">{!! $ldOrg !!}</script>
    <script type="application/ld+json">{!! $ldSite !!}</script>
    @endunless
    @stack('jsonld')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $isAdmin = request()->routeIs('admin.*');
    $isMerchant = request()->routeIs('merchant.*');
    $isAccount = request()->routeIs('account.*');
    $isCabinet = $isMerchant || $isAccount;
    $isPublicPage = ! $isCabinet && ! $isAdmin && ! request()->routeIs('login', 'register', 'password.*', '2fa.*', 'checkout.*');
    $registrationOpen = (bool) \App\Models\Setting::get('registration_enabled', true);

    $merchantUnlocked = isset($currentMerchant) && $currentMerchant->featuresUnlocked();
    $adminNav = [
        [__('ui.admin.overview'), 'admin.dashboard', 'gauge', false],
        [__('ui.admin.users'), 'admin.users.index', 'user', false],
        [__('ui.admin.merchants'), 'admin.merchants.index', 'landmark', false],
        [__('ui.admin.invoices'), 'admin.invoices.index', 'file-text', false],
        [__('ui.admin.transactions'), 'admin.transactions.index', 'layers', false],
        [__('ui.admin.wallets'), 'admin.wallets.index', 'wallet', false],
        [__('ui.admin.withdrawals'), 'admin.withdrawals.index', 'banknote', false],
        [__('ui.admin.refunds'), 'admin.refunds.index', 'banknote', false],
        [__('ui.admin.currencies'), 'admin.currencies.index', 'coins', false],
        [__('ui.blog'), 'admin.blog.index', 'newspaper', false],
        [__('ui.admin.pages'), 'admin.pages.index', 'layout', false],
        [__('ui.admin.modules'), 'admin.modules.index', 'layers', false],
        [__('ui.admin.support'), 'admin.contact.index', 'message-circle', false],
        [__('ui.admin.newsletter'), 'admin.newsletter.index', 'message-circle', false],
        [__('ui.settings'), 'admin.settings.index', 'database', false],
        [__('ui.admin.audit_logs'), 'admin.audit-logs.index', 'book', false],
    ];
@endphp
<body class="app-shell min-h-screen overflow-x-hidden bg-white antialiased">
    <div class="min-h-screen">
        <header class="sticky top-0 z-40 px-4 pt-3 sm:px-6">
            <div class="mx-auto flex {{ ($isCabinet ?? false) || ($isAdmin ?? false) ? 'h-16 max-w-7xl' : 'h-20 max-w-6xl' }} items-center justify-between gap-4 rounded-2xl border border-slate-100 bg-white px-5 shadow-lg shadow-slate-200/60 backdrop-blur-xl sm:px-7">

                {{-- Brand --}}
                <a href="{{ auth()->check() ? route('account.dashboard') : route('home') }}" class="flex shrink-0 items-center">
                    <x-logo class="{{ ($isCabinet ?? false) || ($isAdmin ?? false) ? 'h-12 w-auto max-w-[200px]' : 'h-20 w-auto max-w-[260px]' }}" />
                </a>

                @guest
                    <div class="flex flex-1 items-center justify-end gap-2 sm:gap-3">
                        <x-button href="{{ route('login') }}" variant="secondary" class="min-w-20 rounded-full border-slate-200 px-4 text-slate-900 hover:border-blue-200 hover:text-blue-600 sm:min-w-28 sm:px-6">{{ __('ui.login') }}</x-button>
                        @if($registrationOpen)
                            <x-button href="{{ route('register') }}" class="min-w-24 rounded-full px-4 sm:min-w-36 sm:px-6">{{ __('ui.sign_up') }}</x-button>
                        @endif
                    </div>
                @else
                    <div class="flex items-center gap-2.5">
                        @if(auth()->user()->isAdmin() && $isAdmin)
                            <a href="{{ route('account.dashboard') }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white">
                                <x-icon name="arrow-left" class="h-4 w-4" />
                                <span class="hidden sm:inline">{{ __('ui.back_cabinet') }}</span>
                            </a>
                        @endif

                        @if($isPublicPage)
                            <x-button href="{{ route('account.dashboard') }}" class="hidden rounded-full px-6 sm:inline-flex">{{ __('public.home.dashboard') }}</x-button>
                            <x-button href="{{ route('account.dashboard') }}" class="rounded-full px-4 sm:hidden">{{ __('ui.dashboard') }}</x-button>
                        @else
                            <x-user-menu :user="$headerUser" />
                        @endif
                    </div>
                @endguest
            </div>

        </header>

        @if($isCabinet)
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[16rem_1fr] lg:px-8">
                @include('partials.cabinet-sidebar')
                <main class="min-w-0">
                    @include('partials.flash')
                    @yield('content')
                </main>
            </div>
        @elseif($isAdmin)
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[16rem_1fr] lg:px-8">
                <aside class="lg:sticky lg:top-24 lg:h-[calc(100vh-7rem)]">
                    <div class="rounded-3xl border border-slate-200 bg-white/86 p-3 shadow-xl shadow-slate-200/60 backdrop-blur">
                        <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('ui.admin.console') }}</p>
                        <nav class="flex gap-2 overflow-x-auto lg:block lg:space-y-1">
                            @foreach($adminNav as [$label, $route, $icon, $locked])
                                @php $active = request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route)); @endphp
                                <a href="{{ route($route) }}" class="flex shrink-0 items-center gap-2 rounded-2xl px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                                    <x-icon :name="$icon" class="h-4 w-4" />
                                    {{ $label }}
                                </a>
                            @endforeach
                        </nav>
                        <div class="mt-3 border-t border-slate-100 pt-3">
                            <x-language-switcher />
                        </div>
                    </div>
                </aside>
                <main class="min-w-0">
                    @include('partials.flash')
                    @yield('content')
                </main>
            </div>
        @else
            <main>
                @include('partials.flash')
                @yield('content')
            </main>
        @endif
    </div>

    @include('partials.toast')
</body>
</html>
