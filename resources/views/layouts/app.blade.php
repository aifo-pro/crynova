<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    // ── SEO context ───────────────────────────────────────────────
    $siteNameSetting = (string) \App\Models\Setting::get('site_name', 'Crynova');
    $siteDescriptionSetting = (string) \App\Models\Setting::get('site_description', __('public.home.subtitle'));
    $siteUrlSetting = trim((string) \App\Models\Setting::get('site_url', url('/'))) ?: url('/');
    $sameAs = array_values(array_filter([
        \App\Models\Setting::get('telegram_support_url', ''),
        \App\Models\Setting::get('instagram_url', ''),
        \App\Models\Setting::get('youtube_url', ''),
        \App\Models\Setting::get('telegram_bot_url', ''),
    ]));
    $seoRoute   = optional(request()->route())->getName() ?? '';
    $seoPrivate = \Illuminate\Support\Str::startsWith($seoRoute, ['account.', 'merchant.', 'admin.', '2fa.', 'checkout.', 'verification.'])
        || in_array($seoRoute, ['login', 'register', 'password.request', 'logout'], true);
    // The inline @section('title', '...') form pre-escapes its content, so decode
    // entities once here; the {{ }} below re-escapes exactly once (no double &#039;).
    $seoTitle   = trim(html_entity_decode($__env->yieldContent('title', $siteNameSetting), ENT_QUOTES, 'UTF-8'));
    $seoFullTitle = $seoTitle === $siteNameSetting ? __('public.home.title').' | '.$siteNameSetting : $seoTitle.' | '.$siteNameSetting;
    $seoDesc    = trim(html_entity_decode($__env->yieldContent('meta_description', $siteDescriptionSetting), ENT_QUOTES, 'UTF-8'));
    $seoCanonical = url()->current();
    // OG/Twitter preview image: admin-uploaded one (absolute URL) or the logo fallback.
    $ogImagePath = trim((string) \App\Models\Setting::get('og_image', ''));
    $seoImage   = $ogImagePath !== ''
        ? asset('storage/'.ltrim($ogImagePath, '/'))
        : asset('assets/crynova/logo-light.png');
    // Per-page override (e.g. blog cover image) takes precedence.
    $pageOgImage = trim($__env->yieldContent('og_image'));
    if ($pageOgImage !== '') {
        $seoImage = \Illuminate\Support\Str::startsWith($pageOgImage, ['http://', 'https://']) ? $pageOgImage : asset(ltrim($pageOgImage, '/'));
    }
    $seoType    = trim($__env->yieldContent('og_type')) ?: 'website';
    $seoLocale  = ['uk' => 'uk_UA', 'en' => 'en_US', 'pl' => 'pl_PL', 'ru' => 'ru_RU'][app()->getLocale()] ?? 'uk_UA';
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
        $googleAnalyticsId = preg_replace('/[^A-Za-z0-9\-]/', '', (string) \App\Models\Setting::get('google_analytics_id', ''));
        $googleTagManagerId = preg_replace('/[^A-Za-z0-9\-]/', '', (string) \App\Models\Setting::get('google_tag_manager_id', ''));
    @endphp
    @if($googleSiteVerification !== '')
        <meta name="google-site-verification" content="{{ $googleSiteVerification }}">
    @endif

    @if($googleTagManagerId !== '')
        {{-- Google Tag Manager --}}
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $googleTagManagerId }}');</script>
    @endif

    @if($googleAnalyticsId !== '')
        {{-- Google Analytics (gtag.js) --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleAnalyticsId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $googleAnalyticsId }}');
        </script>
    @endif

    {{-- Primary SEO --}}
    <title>{{ $seoFullTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}">
    <link rel="canonical" href="{{ $seoCanonical }}">
    @if($seoPrivate)
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
        <link rel="alternate" hreflang="uk" href="{{ locale_path('uk') }}">
        <link rel="alternate" hreflang="en" href="{{ locale_path('en') }}">
        <link rel="alternate" hreflang="pl" href="{{ locale_path('pl') }}">
        <link rel="alternate" hreflang="ru" href="{{ locale_path('ru') }}">
        <link rel="alternate" hreflang="x-default" href="{{ locale_path('uk') }}">
    @endif

    {{-- Open Graph --}}
    <meta property="og:type" content="{{ $seoType }}">
    @hasSection('article_published')<meta property="article:published_time" content="@yield('article_published')">@endif
    @hasSection('article_modified')<meta property="article:modified_time" content="@yield('article_modified')">@endif
    <meta property="og:site_name" content="{{ $siteNameSetting }}">
    <meta property="og:title" content="{{ $seoFullTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:image:secure_url" content="{{ $seoImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $siteNameSetting }}">
    <meta property="og:locale" content="{{ $seoLocale }}">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoFullTitle }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <meta name="twitter:image:alt" content="{{ $siteNameSetting }}">

    @php $fav = 'assets/crynova/favicon'; @endphp
    <link rel="icon" href="{{ asset($fav.'/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset($fav.'/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset($fav.'/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset($fav.'/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset($fav.'/site.webmanifest') }}">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    {{-- Organization + WebSite structured data (public pages) --}}
    @unless($seoPrivate)
    @php
        $orgId  = rtrim($siteUrlSetting, '/') . '/#organization';
        $siteId = rtrim($siteUrlSetting, '/') . '/#website';
        $supportUrl = trim((string) \App\Models\Setting::get('telegram_support_url', ''));

        // Organization — the publisher entity referenced by WebSite and article schema.
        $org = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Organization',
            '@id'         => $orgId,
            'name'        => $siteNameSetting,
            'url'         => $siteUrlSetting,
            'description' => $seoDesc,
            'logo'        => [
                '@type'  => 'ImageObject',
                'url'    => asset('assets/crynova/logo-light.png'),
                'width'  => 512,
                'height' => 512,
            ],
            'image' => $seoImage,
        ];
        if (! empty($sameAs)) {
            $org['sameAs'] = array_values($sameAs);
        }
        if ($supportUrl !== '') {
            $org['contactPoint'] = [[
                '@type'             => 'ContactPoint',
                'contactType'       => 'customer support',
                'url'               => $supportUrl,
                'availableLanguage' => ['Ukrainian', 'English', 'Polish'],
            ]];
        }

        // WebSite — linked to the publisher Organization.
        $site = [
            '@context'   => 'https://schema.org',
            '@type'      => 'WebSite',
            '@id'        => $siteId,
            'name'       => $siteNameSetting,
            'url'        => $siteUrlSetting,
            'inLanguage' => app()->getLocale(),
            'publisher'  => ['@id' => $orgId],
        ];

        // BreadcrumbList — Home › current page (skipped on the home page,
        // or when a page supplies its own breadcrumb via @section('custom_breadcrumb')).
        $crumbs = null;
        $hasCustomCrumb = trim($__env->yieldContent('custom_breadcrumb')) !== '';
        if (! request()->routeIs('home') && ! $hasCustomCrumb) {
            $items = [[
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => $siteNameSetting,
                'item'     => $siteUrlSetting,
            ]];
            if ($seoTitle !== $siteNameSetting && $seoTitle !== '') {
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => 2,
                    'name'     => $seoTitle,
                    'item'     => $seoCanonical,
                ];
            }
            $crumbs = [
                '@context'        => 'https://schema.org',
                '@type'           => 'BreadcrumbList',
                'itemListElement' => $items,
            ];
        }
    @endphp
    <script type="application/ld+json">{!! json_encode($org, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <script type="application/ld+json">{!! json_encode($site, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @if($crumbs)<script type="application/ld+json">{!! json_encode($crumbs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>@endif
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
        [__('ui.admin.tickets'), 'admin.support.index', 'message-circle', false],
        [__('ui.admin.newsletter'), 'admin.newsletter.index', 'message-circle', false],
        [__('ui.settings'), 'admin.settings.index', 'database', false],
        [__('ui.admin.audit_logs'), 'admin.audit-logs.index', 'book', false],
    ];
@endphp
<body class="app-shell min-h-screen overflow-x-hidden bg-white antialiased">
    @if(session('app_preloader'))
        @include('partials.preloader')
    @endif
    @if($googleTagManagerId !== '')
        {{-- Google Tag Manager (noscript) --}}
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $googleTagManagerId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif
    <div class="min-h-screen">
        @if(session('impersonator_id'))
            <div class="sticky top-0 z-50 flex flex-wrap items-center justify-center gap-3 bg-amber-500 px-4 py-2 text-center text-sm font-bold text-amber-950">
                <span class="inline-flex items-center gap-2">
                    <x-icon name="shield" class="h-4 w-4" />
                    Режим імперсонації: ви увійшли як {{ auth()->user()?->email }}
                </span>
                <form method="POST" action="{{ route('impersonate.stop') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-full bg-amber-950 px-4 py-1 text-xs font-bold text-amber-50 transition hover:bg-amber-900">
                        <x-icon name="arrow-left" class="h-3.5 w-3.5" /> Повернутися до адміна
                    </button>
                </form>
            </div>
        @endif
        <header class="sticky top-0 z-40 {{ ($isCabinet ?? false) || ($isAdmin ?? false) ? 'px-2 pt-3 sm:px-3' : 'px-4 pt-3 sm:px-6' }}">
            <div @class([
                'mx-auto flex items-center justify-between gap-4 rounded-2xl border border-slate-100 bg-white shadow-lg shadow-slate-200/60 backdrop-blur-xl overflow-visible',
                'h-16 max-w-7xl pl-2 pr-4 sm:pl-3 sm:pr-5' => ($isCabinet ?? false) || ($isAdmin ?? false),
                'h-20 max-w-6xl px-5 sm:px-7' => ! (($isCabinet ?? false) || ($isAdmin ?? false)),
            ])>

                {{-- Brand --}}
                <a href="{{ auth()->check() ? route('account.dashboard') : lroute('home') }}" class="flex shrink-0 items-center overflow-visible py-1">
                    <x-logo />
                </a>

                @if(($isAdmin ?? false) && auth()->check())
                    <form method="GET" action="{{ route('admin.search') }}" class="relative hidden max-w-md flex-1 md:block">
                        <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input name="q" value="{{ request('q') }}"
                               class="h-10 w-full rounded-full border border-slate-200 bg-slate-50 pl-10 pr-4 text-sm text-slate-700 transition placeholder:text-slate-400 focus:border-blue-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100"
                               placeholder="Пошук: UUID, email, домен, адреса, tx hash...">
                    </form>
                @endif

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
                <aside class="lg:sticky lg:top-24 lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <div class="rounded-3xl border border-slate-200 bg-white/86 p-3 shadow-xl shadow-slate-200/60 backdrop-blur">
                        <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('ui.admin.console') }}</p>
                        @php
                            // Attention badges: items in the admin panel awaiting action.
                            $navBadges = [
                                'admin.support.index'     => \App\Models\SupportTicket::where('admin_unread', true)->count(),
                                'admin.contact.index'     => \App\Models\ContactMessage::where('status', 'new')->count(),
                                'admin.merchants.index'   => \App\Models\Merchant::where('status', \App\Models\Merchant::STATUS_MODERATION)->count(),
                                'admin.withdrawals.index' => \App\Models\Withdrawal::where('status', 'pending')->count(),
                                'admin.refunds.index'     => \App\Models\Refund::where('status', 'pending')->count(),
                            ];
                        @endphp
                        <nav class="flex gap-2 overflow-x-auto lg:block lg:space-y-1">
                            @foreach($adminNav as [$label, $route, $icon, $locked])
                                @php $active = request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route)); $badge = $navBadges[$route] ?? 0; @endphp
                                <a href="{{ route($route) }}" class="flex shrink-0 items-center gap-2 rounded-2xl px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                                    <x-icon :name="$icon" class="h-4 w-4" />
                                    {{ $label }}
                                    @if($badge > 0)
                                        <span class="ml-auto grid h-5 min-w-5 place-items-center rounded-full {{ $active ? 'bg-white text-blue-600' : 'bg-rose-500 text-white' }} px-1.5 text-[11px] font-bold">{{ $badge }}</span>
                                    @endif
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
            @if($isPublicPage)
                @include('partials.public-footer')
            @endif
        @endif
    </div>

    @include('partials.toast')
    @if($isPublicPage)
        @include('partials.cookie-consent')
    @endif
    @stack('scripts')
</body>
</html>
