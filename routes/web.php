<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController as PublicContactController;
use App\Http\Controllers\Api\IpsController;
use App\Http\Controllers\Merchant;
use App\Http\Controllers\NewsletterController as PublicNewsletterController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureMerchantActive;
use App\Http\Middleware\Require2FA;
use App\Http\Middleware\SupportScope;
use Illuminate\Support\Facades\Route;

// ── SEO: sitemap.xml ──────────────────────────────────────────────────
// robots.txt is served as a static file from public/robots.txt.
Route::get('/sitemap.xml', function () {
    // Each entry is a locale-agnostic path; the view emits a uk/en/pl <url>
    // for it with hreflang alternates so Google indexes every language.
    $pages = [
        ['name' => 'home',          'priority' => '1.0', 'freq' => 'daily'],
        ['name' => 'pricing',       'priority' => '0.8', 'freq' => 'weekly'],
        ['name' => 'coins',         'priority' => '0.7', 'freq' => 'weekly'],
        ['name' => 'developers',    'priority' => '0.7', 'freq' => 'weekly'],
        ['name' => 'api.docs',      'priority' => '0.7', 'freq' => 'weekly'],
        ['name' => 'contact',       'priority' => '0.5', 'freq' => 'monthly'],
        ['name' => 'blog',          'priority' => '0.6', 'freq' => 'daily'],
        ['name' => 'legal.terms',   'priority' => '0.3', 'freq' => 'yearly'],
        ['name' => 'legal.privacy', 'priority' => '0.3', 'freq' => 'yearly'],
    ];

    $entries = [];
    foreach ($pages as $p) {
        $entries[] = [
            'path'     => route($p['name'], [], false),
            'priority' => $p['priority'],
            'freq'     => $p['freq'],
        ];
    }

    foreach (\App\Models\BlogPost::query()->published()->latest('published_at')->limit(500)->get() as $post) {
        $entries[] = [
            'path'     => route('blog.show', ['post' => $post->slug], false),
            'priority' => '0.6',
            'freq'     => 'monthly',
            'lastmod'  => optional($post->updated_at)->toAtomString(),
        ];
    }

    return response()->view('sitemap', compact('entries'))->header('Content-Type', 'application/xml');
});

// ── Public (functional, not localized by URL) ─────────────────────────
// Legacy: the News section was merged into the Blog — 301 so old links keep their SEO weight.
Route::redirect('/news', '/blog', 301);
Route::redirect('/news/{any}', '/blog', 301)->where('any', '.*');
Route::post('/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['uk', 'en', 'pl', 'ru'], true), 404);

    session(['locale' => $locale]);

    return back();
})->name('locale.switch');
Route::get('/newsletter/unsubscribe/{token}', [PublicNewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::get('/ips.json', [IpsController::class, 'json'])->name('ips.json');
// PHP SDK download — zipped on the fly from resources/sdk (not localized).
Route::get('/api/sdk/download', [\App\Http\Controllers\SdkController::class, 'download'])->name('api.sdk.download');

// ── Public content (localized URLs: root = uk, /en/…, /pl/…) ──────────
// Symfony can't match a leading OPTIONAL prefix ("/pricing" won't match
// "{locale?}/pricing"), so we register each page twice: once unprefixed and
// named (uk — what route()/redirects resolve), and once under a REQUIRED
// {locale} prefix (en|pl) and anonymous (only matched for /en/… and /pl/…).
// Prefixed links in views are produced by the lroute() helper.
$publicContent = function (bool $named): void {
    $n = fn ($route, string $name) => $named ? $route->name($name) : $route;

    $n(Route::get('/', fn () => view('welcome', [
        'latestPosts' => \App\Models\BlogPost::query()->published()->latest('published_at')->limit(3)->get(),
    ])), 'home');
    $n(Route::view('/pricing', 'public.pricing'), 'pricing');
    $n(Route::view('/supported-coins', 'public.coins'), 'coins');
    $n(Route::view('/developers', 'public.developers'), 'developers');
    $n(Route::view('/api', 'public.api-docs'), 'api.docs');
    Route::view('/docs', 'public.api-docs');
    $n(Route::get('/api/sdk', [\App\Http\Controllers\SdkController::class, 'page']), 'api.sdk');
    $n(Route::view('/contact', 'public.contact'), 'contact');
    $n(Route::post('/contact', [PublicContactController::class, 'store']), 'contact.store');
    $n(Route::get('/blog', fn () => view('public.blog', [
        'posts' => \App\Models\BlogPost::query()->published()->latest('published_at')->paginate(9),
    ])), 'blog');
    $n(Route::get('/blog/{post:slug}', function (\App\Models\BlogPost $post) {
        abort_unless($post->status === 'published' && $post->published_at, 404);

        return view('public.blog-show', compact('post'));
    }), 'blog.show');
    $n(Route::post('/blog/{post:slug}/rate', [\App\Http\Controllers\BlogRatingController::class, 'store'])
        ->middleware('throttle:20,1'), 'blog.rate');

    $legal = function () use ($named, $n) {
        $n(Route::view('/terms', 'legal.terms'), 'terms');
        $n(Route::view('/privacy', 'legal.privacy'), 'privacy');
        $n(Route::view('/aml-kyc', 'legal.aml-kyc'), 'aml-kyc');
        $n(Route::view('/risk-disclosure', 'legal.risk-disclosure'), 'risk-disclosure');
    };
    $named
        ? Route::prefix('legal')->name('legal.')->group($legal)
        : Route::prefix('legal')->group($legal);
};

// uk at the root (named — these back route() and controller redirects).
$publicContent(true);
// en / pl prefixed copies (anonymous — only matched for incoming /en, /pl).
// Literal prefixes (not a {locale} param) so there is no parent route parameter
// and Laravel never tries to scope {post:slug}/{page:slug} to it.
foreach (['en', 'pl', 'ru'] as $loc) {
    Route::prefix($loc)->group(fn () => $publicContent(false));
}

// ── Auth ──────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/forgot-password', fn () => view('auth.forgot-password'))->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:5,1');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update')->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Return to the admin account after impersonation (impersonated user is not an admin,
// so this lives outside the admin group — the session flag is the authorization).
Route::post('/impersonate/stop', [Admin\UserController::class, 'stopImpersonating'])
    ->name('impersonate.stop')->middleware('auth');

Route::middleware('guest')->prefix('auth')->name('auth.')->group(function () {
    Route::get('/google', [SocialAuthController::class, 'redirectGoogle'])->name('google.redirect');
    Route::get('/google/callback', [SocialAuthController::class, 'callbackGoogle'])->name('google.callback');
    Route::get('/telegram/callback', [SocialAuthController::class, 'callbackTelegram'])->name('telegram.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// 2FA
Route::middleware('auth')->prefix('auth/2fa')->name('2fa.')->group(function () {
    Route::get('/verify', [TwoFactorController::class, 'showVerify'])->name('verify');
    Route::post('/verify', [TwoFactorController::class, 'verify']);
    Route::get('/setup', [TwoFactorController::class, 'showSetup'])->name('setup');
    Route::post('/setup', [TwoFactorController::class, 'confirmSetup']);
    Route::delete('/disable', [TwoFactorController::class, 'disable'])->name('disable');
});

// ── Checkout (public) ─────────────────────────────────────────────────
Route::prefix('pay')->name('checkout.')->middleware('throttle:60,1')->group(function () {
    Route::get('/{uuid}', [CheckoutController::class, 'show'])->name('show');
    Route::get('/{uuid}/status', [CheckoutController::class, 'status'])->name('status');
    Route::post('/{uuid}/currency', [CheckoutController::class, 'selectCurrency'])->name('select-currency');
    // Payment links — reusable payment URLs
    Route::get('/link/{token}', [CheckoutController::class, 'paymentLink'])->name('link');
    Route::post('/link/{token}', [CheckoutController::class, 'paymentLinkCreate'])->name('link.create');
    // Hosted per-merchant POS / donation page
    Route::get('/pos/{shop}', [CheckoutController::class, 'pos'])->name('pos');
    Route::post('/pos/{shop}', [CheckoutController::class, 'posCreate'])->name('pos.create');
});

// ── Admin ─────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', Require2FA::class, EnsureAdmin::class, SupportScope::class])->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search', [Admin\SearchController::class, 'index'])->name('search');
    Route::get('/health', [Admin\HealthController::class, 'index'])->name('health');
    Route::get('/notifications/feed', [Admin\NotificationController::class, 'feed'])->name('notifications.feed');

    Route::prefix('aml')->name('aml.')->group(function () {
        Route::get('/', [Admin\AmlController::class, 'index'])->name('index');
        Route::post('/release', [Admin\AmlController::class, 'release'])->name('release');
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [Admin\UserController::class, 'index'])->name('index');
        Route::get('/create', [Admin\UserController::class, 'create'])->name('create');
        Route::post('/', [Admin\UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [Admin\UserController::class, 'edit'])->name('edit');
        Route::patch('/{user}', [Admin\UserController::class, 'update'])->name('update');
        Route::post('/{user}/password', [Admin\UserController::class, 'updatePassword'])->name('password');
        Route::post('/{user}/notes', [Admin\UserController::class, 'updateNotes'])->name('notes');
        Route::post('/{user}/departments', [Admin\UserController::class, 'updateDepartments'])->name('departments');
        Route::post('/{user}/reset-2fa', [Admin\UserController::class, 'resetTwoFactor'])->name('reset-2fa');
        Route::post('/{user}/toggle', [Admin\UserController::class, 'toggleActive'])->name('toggle');
        Route::post('/{user}/block', [Admin\UserController::class, 'block'])->name('block');
        Route::post('/{user}/unblock', [Admin\UserController::class, 'unblock'])->name('unblock');
        Route::post('/{user}/impersonate', [Admin\UserController::class, 'impersonate'])->name('impersonate');
        Route::delete('/{user}', [Admin\UserController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/restore', [Admin\UserController::class, 'restore'])->name('restore');
    });

    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [Admin\InvoiceController::class, 'index'])->name('index');
        Route::get('/export', [Admin\InvoiceController::class, 'export'])->name('export');
        Route::get('/{invoice}', [Admin\InvoiceController::class, 'show'])->name('show');
        Route::post('/{invoice}/recheck', [Admin\InvoiceController::class, 'recheck'])->name('recheck');
        Route::post('/{invoice}/cancel', [Admin\InvoiceController::class, 'cancel'])->name('cancel');
        Route::post('/{invoice}/webhooks/{log}/resend', [Admin\InvoiceController::class, 'resendWebhook'])->name('webhooks.resend');
    });

    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [Admin\TransactionController::class, 'index'])->name('index');
    });

    Route::prefix('wallets')->name('wallets.')->group(function () {
        Route::get('/', [Admin\WalletController::class, 'index'])->name('index');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [Admin\SettingController::class, 'index'])->name('index');
        Route::post('/', [Admin\SettingController::class, 'update'])->name('update');
    });

    Route::prefix('newsletter')->name('newsletter.')->group(function () {
        Route::get('/', [Admin\NewsletterController::class, 'index'])->name('index');
        Route::post('/', [Admin\NewsletterController::class, 'send'])->name('send');
    });

    Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
        Route::get('/', [Admin\AuditLogController::class, 'index'])->name('index');
    });

    Route::prefix('merchants')->name('merchants.')->group(function () {
        Route::get('/', [Admin\MerchantController::class, 'index'])->name('index');
        Route::post('/bulk', [Admin\MerchantController::class, 'bulk'])->name('bulk');
        Route::get('/{merchant}', [Admin\MerchantController::class, 'show'])->name('show');
        Route::post('/{merchant}/approve', [Admin\MerchantController::class, 'approve'])->name('approve');
        Route::post('/{merchant}/reject', [Admin\MerchantController::class, 'reject'])->name('reject');
        Route::post('/{merchant}/block', [Admin\MerchantController::class, 'block'])->name('block');
        Route::post('/{merchant}/note', [Admin\MerchantController::class, 'updateNote'])->name('note');
        Route::post('/{merchant}/description', [Admin\MerchantController::class, 'updateDescription'])->name('description');
        Route::post('/{merchant}/base-currency', [Admin\MerchantController::class, 'updateBaseCurrency'])->name('base-currency');
        Route::post('/{merchant}/limits', [Admin\MerchantController::class, 'updateLimits'])->name('limits');
        Route::post('/{merchant}/adjust-balance', [Admin\MerchantController::class, 'adjustBalance'])->name('adjust-balance');
        Route::post('/{merchant}/payment-methods', [Admin\MerchantController::class, 'updatePaymentMethods'])->name('payment-methods');
        Route::post('/{merchant}/webhook-test', [Admin\MerchantController::class, 'testWebhook'])->name('webhook-test');
        Route::post('/{merchant}/rotate-secret', [Admin\MerchantController::class, 'rotateSecret'])->name('rotate-secret');
        Route::delete('/{merchant}', [Admin\MerchantController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('withdrawals')->name('withdrawals.')->group(function () {
        Route::get('/', [Admin\WithdrawalController::class, 'index'])->name('index');
        Route::post('/bulk', [Admin\WithdrawalController::class, 'bulk'])->name('bulk');
        Route::post('/{withdrawal}/approve', [Admin\WithdrawalController::class, 'approve'])->name('approve');
        Route::post('/{withdrawal}/reject', [Admin\WithdrawalController::class, 'reject'])->name('reject');
        Route::post('/{withdrawal}/sent', [Admin\WithdrawalController::class, 'markSent'])->name('sent');
    });

    Route::prefix('blog')->name('blog.')->group(function () {
        Route::get('/', [Admin\BlogController::class, 'index'])->name('index');
        Route::get('/create', [Admin\BlogController::class, 'create'])->name('create');
        Route::post('/upload-image', [Admin\BlogController::class, 'uploadImage'])->name('upload-image');
        Route::post('/', [Admin\BlogController::class, 'store'])->name('store');
        Route::get('/{post}/edit', [Admin\BlogController::class, 'edit'])->name('edit');
        Route::patch('/{post}', [Admin\BlogController::class, 'update'])->name('update');
        Route::delete('/{post}', [Admin\BlogController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('pages')->name('pages.')->group(function () {
        Route::get('/', [Admin\PageController::class, 'index'])->name('index');
        Route::get('/create', [Admin\PageController::class, 'create'])->name('create');
        Route::post('/', [Admin\PageController::class, 'store'])->name('store');
        Route::get('/{page}/edit', [Admin\PageController::class, 'edit'])->name('edit');
        Route::patch('/{page}', [Admin\PageController::class, 'update'])->name('update');
        Route::delete('/{page}', [Admin\PageController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('modules')->name('modules.')->group(function () {
        Route::get('/', [Admin\IntegrationModuleController::class, 'index'])->name('index');
        Route::get('/create', [Admin\IntegrationModuleController::class, 'create'])->name('create');
        Route::post('/', [Admin\IntegrationModuleController::class, 'store'])->name('store');
        Route::get('/{module}/edit', [Admin\IntegrationModuleController::class, 'edit'])->name('edit');
        Route::patch('/{module}', [Admin\IntegrationModuleController::class, 'update'])->name('update');
        Route::delete('/{module}', [Admin\IntegrationModuleController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('contact')->name('contact.')->group(function () {
        Route::get('/', [Admin\ContactController::class, 'index'])->name('index');
        Route::get('/{message}', [Admin\ContactController::class, 'show'])->name('show');
        Route::patch('/{message}', [Admin\ContactController::class, 'update'])->name('update');
    });

    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [Admin\SupportController::class, 'index'])->name('index');
        Route::get('/attachment/{attachment}', [Admin\SupportController::class, 'download'])->name('attachment');
        Route::get('/{ticket}', [Admin\SupportController::class, 'show'])->name('show');
        Route::get('/{ticket}/messages', [Admin\SupportController::class, 'messages'])->name('messages');
        Route::post('/{ticket}/reply', [Admin\SupportController::class, 'reply'])->name('reply');
        Route::post('/{ticket}/close', [Admin\SupportController::class, 'close'])->name('close');
        Route::post('/{ticket}/reopen', [Admin\SupportController::class, 'reopen'])->name('reopen');
        Route::post('/{ticket}/assign', [Admin\SupportController::class, 'assign'])->name('assign');
        Route::post('/{ticket}/priority', [Admin\SupportController::class, 'priority'])->name('priority');
        Route::post('/{ticket}/locale', [Admin\SupportController::class, 'setLocale'])->name('locale');
        Route::post('/{ticket}/note', [Admin\SupportController::class, 'addNote'])->name('note');
        Route::post('/{ticket}/transfer', [Admin\SupportController::class, 'transfer'])->name('transfer');
    });

    // Support departments (specializations) — full admins only.
    Route::prefix('support-departments')->name('support-departments.')->group(function () {
        Route::get('/', [Admin\SupportDepartmentController::class, 'index'])->name('index');
        Route::get('/create', [Admin\SupportDepartmentController::class, 'create'])->name('create');
        Route::post('/', [Admin\SupportDepartmentController::class, 'store'])->name('store');
        Route::get('/{department}/edit', [Admin\SupportDepartmentController::class, 'edit'])->name('edit');
        Route::patch('/{department}', [Admin\SupportDepartmentController::class, 'update'])->name('update');
        Route::delete('/{department}', [Admin\SupportDepartmentController::class, 'destroy'])->name('destroy');
    });

    // Reply templates / canned answers (FAQ library for support agents)
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [Admin\SupportTemplateController::class, 'index'])->name('index');
        Route::get('/create', [Admin\SupportTemplateController::class, 'create'])->name('create');
        Route::post('/', [Admin\SupportTemplateController::class, 'store'])->name('store');
        Route::get('/{template}/edit', [Admin\SupportTemplateController::class, 'edit'])->name('edit');
        Route::patch('/{template}', [Admin\SupportTemplateController::class, 'update'])->name('update');
        Route::post('/{template}/toggle', [Admin\SupportTemplateController::class, 'toggle'])->name('toggle');
        Route::delete('/{template}', [Admin\SupportTemplateController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('refunds')->name('refunds.')->group(function () {
        Route::get('/', [Admin\RefundController::class, 'index'])->name('index');
        Route::post('/{refund}/approve', [Admin\RefundController::class, 'approve'])->name('approve');
        Route::post('/{refund}/reject', [Admin\RefundController::class, 'reject'])->name('reject');
    });

    Route::prefix('currencies')->name('currencies.')->group(function () {
        Route::get('/', [Admin\CurrencyController::class, 'index'])->name('index');
        Route::get('/create', [Admin\CurrencyController::class, 'create'])->name('create');
        Route::post('/', [Admin\CurrencyController::class, 'store'])->name('store');
        Route::get('/{currency}/edit', [Admin\CurrencyController::class, 'edit'])->name('edit');
        Route::patch('/{currency}', [Admin\CurrencyController::class, 'update'])->name('update');
        Route::post('/{currency}/toggle', [Admin\CurrencyController::class, 'toggleActive'])->name('toggle');
    });
});

// ── Account (user panel) ──────────────────────────────────────────────
// The personal account area: profile, security and merchant management.
// No merchant is required to access it — this is where merchants are created.
Route::prefix('account')->name('account.')->middleware(['auth', Require2FA::class])->group(function () {
    // Главная — aggregate dashboard
    Route::get('/dashboard', [Account\DashboardController::class, 'index'])->name('dashboard');

    // Мои проекты — project list
    Route::get('/projects', [Account\ProjectsController::class, 'index'])->name('projects');

    // Баланс / Платежи — account-level aggregates
    Route::get('/balance', [Account\BalanceController::class, 'index'])->name('balance');
    Route::post('/balance/withdraw', [Account\BalanceController::class, 'withdraw'])->name('balance.withdraw');
    Route::post('/balance/mass-payout', [Account\BalanceController::class, 'massPayout'])->name('balance.mass');
    Route::post('/balance/addresses', [Account\BalanceController::class, 'storeAddress'])->name('balance.addresses.store');
    Route::delete('/balance/addresses/{address}', [Account\BalanceController::class, 'destroyAddress'])->name('balance.addresses.destroy');
    Route::post('/balance/auto-withdraw', [Account\BalanceController::class, 'autoWithdraw'])->name('balance.auto.store');
    Route::delete('/balance/auto-withdraw/{rule}', [Account\BalanceController::class, 'destroyAutoWithdraw'])->name('balance.auto.destroy');
    Route::get('/payments', [Account\PaymentController::class, 'index'])->name('payments');
    Route::get('/payments/create', [Account\PaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [Account\PaymentController::class, 'store'])->name('payments.store');

    // Интеграция / Обмен / Виджет — hub pages
    Route::get('/integration/api', [Account\HubController::class, 'api'])->name('integration.api');
    Route::get('/integration/modules', [Account\HubController::class, 'modules'])->name('integration.modules');
    Route::get('/integration/modules/{module:slug}', [Account\HubController::class, 'showModule'])->name('integration.modules.show');
    Route::get('/integration/modules/{module}/download', [Account\HubController::class, 'downloadModule'])->name('integration.modules.download');
    Route::get('/integration/widget', [Account\HubController::class, 'widget'])->name('integration.widget');
    Route::get('/integration/brandbook', [Account\HubController::class, 'brandbook'])->name('integration.brandbook');
    Route::get('/exchange', [Account\ExchangeController::class, 'index'])->name('exchange');
    Route::post('/exchange/quote', [Account\ExchangeController::class, 'quote'])->name('exchange.quote');
    Route::post('/exchange', [Account\ExchangeController::class, 'exchange'])->name('exchange.execute');

    // Партнёрство / База знаний
    Route::get('/partner', [Account\PartnerController::class, 'index'])->name('partner');
    Route::get('/knowledge', [Account\KnowledgeController::class, 'index'])->name('knowledge');

    // Поддержка (тикеты)
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [Account\SupportController::class, 'index'])->name('index');
        Route::post('/', [Account\SupportController::class, 'store'])->name('store');
        Route::get('/attachment/{attachment}', [Account\SupportController::class, 'download'])->name('attachment');
        Route::get('/{ticket}', [Account\SupportController::class, 'show'])->name('show');
        Route::get('/{ticket}/messages', [Account\SupportController::class, 'messages'])->name('messages');
        Route::post('/{ticket}/reply', [Account\SupportController::class, 'reply'])->name('reply');
        Route::post('/{ticket}/close', [Account\SupportController::class, 'close'])->name('close');
    });

    // ── Настройки аккаунта (вкладки) ──────────────────────────────────
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [Account\SettingsController::class, 'profile'])->name('profile');
        Route::patch('/profile', [Account\SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::patch('/password', [Account\SettingsController::class, 'updatePassword'])->name('password');

        Route::get('/security', [Account\SettingsController::class, 'security'])->name('security');
        Route::post('/security/api-key', [Account\SettingsController::class, 'regenerateApiKey'])->name('security.api-key');

        Route::get('/notifications', [Account\SettingsController::class, 'notifications'])->name('notifications');
        Route::post('/notifications', [Account\SettingsController::class, 'updateNotifications'])->name('notifications.update');

        Route::get('/team', [Account\SettingsController::class, 'team'])->name('team');
        Route::post('/team', [Account\SettingsController::class, 'inviteTeam'])->name('team.invite');
        Route::delete('/team/{member}', [Account\SettingsController::class, 'removeTeam'])->name('team.remove');
    });

    // Legacy profile/security (kept for 2FA redirects)
    Route::get('/profile', [Account\ProfileController::class, 'edit'])->name('profile');
    Route::patch('/profile', [Account\ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/password', [Account\ProfileController::class, 'updatePassword'])->name('password');
    Route::get('/security', [Account\ProfileController::class, 'security'])->name('security');

    // Merchant creation
    Route::get('/merchants/create', [Account\MerchantController::class, 'create'])->name('merchants.create');
    Route::post('/merchants', [Account\MerchantController::class, 'store'])->name('merchants.store');
});

// ── Merchant workspace (scoped by {merchant}) ─────────────────────────
// Every page is scoped to a specific merchant the user owns. Control and
// verification pages are always accessible; feature pages unlock once the
// merchant status is "active" (EnsureMerchantActive).
Route::prefix('merchant/{merchant}')->name('merchant.')
    ->middleware(['auth', Require2FA::class, 'merchant.owner'])
    ->group(function () {

    // Always accessible (any lifecycle status)
    Route::get('/', [Merchant\ControlController::class, 'index'])->name('control');
    Route::post('/resubmit', [Merchant\ControlController::class, 'resubmit'])->name('resubmit');
    Route::get('/verification', [Merchant\VerificationController::class, 'index'])->name('verification');
    Route::post('/verification', [Merchant\VerificationController::class, 'verify'])->name('verification.verify');
    Route::post('/test-mode', [Merchant\ControlController::class, 'toggleTestMode'])->name('test-mode');

    // ── Project settings (tabbed, accessible at any status) ───────────
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [Merchant\SettingsController::class, 'project'])->name('project');
        Route::post('/', [Merchant\SettingsController::class, 'updateProject'])->name('project.update');
        Route::delete('/', [Merchant\SettingsController::class, 'destroy'])->name('destroy');

        Route::get('/integration', [Merchant\SettingsController::class, 'integration'])->name('integration');
        Route::post('/integration', [Merchant\SettingsController::class, 'updateIntegration'])->name('integration.update');
        Route::post('/integration/secret', [Merchant\SettingsController::class, 'regenerateSecret'])->name('integration.secret');

        Route::get('/currencies', [Merchant\SettingsController::class, 'currencies'])->name('currencies');
        Route::post('/currencies', [Merchant\SettingsController::class, 'updateCurrencies'])->name('currencies.update');

        Route::get('/fees', [Merchant\SettingsController::class, 'fees'])->name('fees');
        Route::post('/fees', [Merchant\SettingsController::class, 'updateFees'])->name('fees.update');

        Route::get('/wallets', [Merchant\SettingsController::class, 'wallets'])->name('wallets');
        Route::post('/wallets', [Merchant\SettingsController::class, 'storeWallet'])->name('wallets.store');
        Route::delete('/wallets/{wallet}', [Merchant\SettingsController::class, 'destroyWallet'])->name('wallets.destroy');

        Route::get('/autoconversion', [Merchant\SettingsController::class, 'autoconversion'])->name('autoconversion');
        Route::post('/autoconversion', [Merchant\SettingsController::class, 'updateAutoconversion'])->name('autoconversion.update');

        Route::get('/constructor', [Merchant\SettingsController::class, 'constructor'])->name('constructor');
        Route::post('/constructor', [Merchant\SettingsController::class, 'updateConstructor'])->name('constructor.update');

        Route::get('/widget', [Merchant\SettingsController::class, 'widget'])->name('widget');
        Route::post('/widget', [Merchant\SettingsController::class, 'updateWidget'])->name('widget.update');
    });

    // ── Feature pages (locked until merchant is active) ───────────────
    Route::middleware('merchant.active')->group(function () {
        Route::get('/dashboard', [Merchant\DashboardController::class, 'index'])->name('dashboard');

        // Invoices
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [Merchant\InvoiceController::class, 'index'])->name('index');
            Route::get('/create', [Merchant\InvoiceController::class, 'create'])->name('create');
            Route::post('/', [Merchant\InvoiceController::class, 'store'])->name('store');
            Route::get('/{uuid}', [Merchant\InvoiceController::class, 'show'])->name('show');
        });

        // Balances
        Route::get('/balances', [Merchant\BalanceController::class, 'index'])->name('balances.index');

        // Webhooks
        Route::prefix('webhooks')->name('webhooks.')->group(function () {
            Route::get('/', [Merchant\WebhookController::class, 'index'])->name('index');
            Route::post('/', [Merchant\WebhookController::class, 'save'])->name('save');
            Route::post('/regenerate-secret', [Merchant\WebhookController::class, 'regenerateSecret'])->name('regenerate-secret');
        });

        // Documentation
        Route::get('/documentation', fn (\App\Models\Merchant $merchant) => view('merchant.docs.index', compact('merchant')))->name('docs.index');

        // API Keys
        Route::prefix('api-keys')->name('api-keys.')->group(function () {
            Route::get('/', [Merchant\ApiKeyController::class, 'index'])->name('index');
            Route::post('/', [Merchant\ApiKeyController::class, 'store'])->name('store');
            Route::delete('/{apiKey}', [Merchant\ApiKeyController::class, 'revoke'])->name('revoke');
        });

        // Withdrawals
        Route::prefix('withdrawals')->name('withdrawals.')->group(function () {
            Route::get('/', [Merchant\WithdrawalController::class, 'index'])->name('index');
            Route::post('/', [Merchant\WithdrawalController::class, 'store'])->name('store');
        });

        // Payment links
        Route::prefix('payment-links')->name('payment-links.')->group(function () {
            Route::get('/', [Merchant\PaymentLinkController::class, 'index'])->name('index');
            Route::post('/', [Merchant\PaymentLinkController::class, 'store'])->name('store');
            Route::post('/{link}/toggle', [Merchant\PaymentLinkController::class, 'toggle'])->name('toggle');
            Route::delete('/{link}', [Merchant\PaymentLinkController::class, 'destroy'])->name('destroy');
        });

        // Settlement
        Route::get('/settlement', [Merchant\SettlementController::class, 'index'])->name('settlement.index');

        // Widget
        Route::get('/widget', [Merchant\WidgetController::class, 'index'])->name('widget.index');

        // Refunds
        Route::prefix('refunds')->name('refunds.')->group(function () {
            Route::get('/', [Merchant\RefundController::class, 'index'])->name('index');
            Route::post('/', [Merchant\RefundController::class, 'store'])->name('store');
        });
    });
});

// ── CMS pages (admin-managed, e.g. /tos) ────────────────────────────────────
// Registered LAST so explicit routes always take precedence; only unmatched
// single-segment paths fall through to a published Page lookup. Localized so
// /en/tos and /pl/tos resolve to the same page in the chosen language.
$cmsPage = function (\App\Models\Page $page) {
    abort_unless($page->is_published, 404);

    return view('pages.show', compact('page'));
};
Route::get('/{page:slug}', $cmsPage)->name('pages.show');
foreach (['en', 'pl', 'ru'] as $loc) {
    Route::prefix($loc)->group(fn () => Route::get('/{page:slug}', $cmsPage));
}
