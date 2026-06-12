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
use Illuminate\Support\Facades\Route;

// ── SEO: robots.txt & sitemap.xml ─────────────────────────────────────
Route::get('/robots.txt', function () {
    $body = "User-agent: *\n"
        . "Disallow: /account\n"
        . "Disallow: /merchant\n"
        . "Disallow: /admin\n"
        . "Disallow: /pay\n"
        . "Disallow: /auth\n"
        . "Allow: /\n\n"
        . 'Sitemap: ' . url('/sitemap.xml') . "\n";

    return response($body, 200, ['Content-Type' => 'text/plain']);
});

Route::get('/sitemap.xml', function () {
    $urls = [
        ['loc' => route('home'),       'priority' => '1.0', 'freq' => 'daily'],
        ['loc' => route('pricing'),    'priority' => '0.8', 'freq' => 'weekly'],
        ['loc' => route('coins'),      'priority' => '0.7', 'freq' => 'weekly'],
        ['loc' => route('developers'), 'priority' => '0.7', 'freq' => 'weekly'],
        ['loc' => route('api.docs'),   'priority' => '0.7', 'freq' => 'weekly'],
        ['loc' => route('contact'),    'priority' => '0.5', 'freq' => 'monthly'],
        ['loc' => route('blog'),       'priority' => '0.6', 'freq' => 'daily'],
        ['loc' => route('legal.terms'),   'priority' => '0.3', 'freq' => 'yearly'],
        ['loc' => route('legal.privacy'), 'priority' => '0.3', 'freq' => 'yearly'],
    ];

    foreach (\App\Models\BlogPost::query()->published()->latest('published_at')->limit(500)->get() as $post) {
        $urls[] = [
            'loc'      => route('blog.show', $post->slug),
            'priority' => '0.6',
            'freq'     => 'monthly',
            'lastmod'  => optional($post->updated_at)->toAtomString(),
        ];
    }

    return response()->view('sitemap', compact('urls'))->header('Content-Type', 'application/xml');
});

// ── Public ────────────────────────────────────────────────────────────
Route::get('/', fn () => view('welcome'))->name('home');
Route::post('/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['uk', 'en'], true), 404);

    session(['locale' => $locale]);

    return back();
})->name('locale.switch');
Route::get('/newsletter/unsubscribe/{token}', [PublicNewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::get('/ips.json', [IpsController::class, 'json'])->name('ips.json');
Route::view('/pricing', 'public.pricing')->name('pricing');
Route::view('/supported-coins', 'public.coins')->name('coins');
Route::view('/developers', 'public.developers')->name('developers');
Route::view('/api', 'public.api-docs')->name('api.docs');
Route::view('/docs', 'public.api-docs');
Route::view('/contact', 'public.contact')->name('contact');
Route::post('/contact', [PublicContactController::class, 'store'])->name('contact.store');
Route::get('/blog', fn () => view('public.blog', [
    'posts' => \App\Models\BlogPost::query()->published()->latest('published_at')->paginate(9),
]))->name('blog');
Route::get('/blog/{post:slug}', function (\App\Models\BlogPost $post) {
    abort_unless($post->status === 'published' && $post->published_at, 404);

    return view('public.blog-show', compact('post'));
})->name('blog.show');
Route::post('/blog/{post:slug}/rate', [\App\Http\Controllers\BlogRatingController::class, 'store'])
    ->middleware('throttle:20,1')->name('blog.rate');

// ── Auth ──────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/forgot-password', fn () => view('auth.forgot-password'))->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

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

// ── Legal ─────────────────────────────────────────────────────────────
Route::prefix('legal')->name('legal.')->group(function () {
    Route::view('/terms', 'legal.terms')->name('terms');
    Route::view('/privacy', 'legal.privacy')->name('privacy');
    Route::view('/aml-kyc', 'legal.aml-kyc')->name('aml-kyc');
    Route::view('/risk-disclosure', 'legal.risk-disclosure')->name('risk-disclosure');
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
Route::prefix('admin')->name('admin.')->middleware(['auth', Require2FA::class, EnsureAdmin::class])->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [Admin\UserController::class, 'index'])->name('index');
        Route::get('/create', [Admin\UserController::class, 'create'])->name('create');
        Route::post('/', [Admin\UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [Admin\UserController::class, 'edit'])->name('edit');
        Route::patch('/{user}', [Admin\UserController::class, 'update'])->name('update');
        Route::post('/{user}/password', [Admin\UserController::class, 'updatePassword'])->name('password');
        Route::post('/{user}/reset-2fa', [Admin\UserController::class, 'resetTwoFactor'])->name('reset-2fa');
        Route::post('/{user}/toggle', [Admin\UserController::class, 'toggleActive'])->name('toggle');
        Route::post('/{user}/block', [Admin\UserController::class, 'block'])->name('block');
        Route::post('/{user}/unblock', [Admin\UserController::class, 'unblock'])->name('unblock');
        Route::delete('/{user}', [Admin\UserController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/restore', [Admin\UserController::class, 'restore'])->name('restore');
    });

    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [Admin\InvoiceController::class, 'index'])->name('index');
        Route::get('/{invoice}', [Admin\InvoiceController::class, 'show'])->name('show');
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
        Route::get('/{merchant}', [Admin\MerchantController::class, 'show'])->name('show');
        Route::post('/{merchant}/approve', [Admin\MerchantController::class, 'approve'])->name('approve');
        Route::post('/{merchant}/reject', [Admin\MerchantController::class, 'reject'])->name('reject');
        Route::post('/{merchant}/block', [Admin\MerchantController::class, 'block'])->name('block');
        Route::post('/{merchant}/note', [Admin\MerchantController::class, 'updateNote'])->name('note');
        Route::post('/{merchant}/description', [Admin\MerchantController::class, 'updateDescription'])->name('description');
        Route::post('/{merchant}/base-currency', [Admin\MerchantController::class, 'updateBaseCurrency'])->name('base-currency');
        Route::post('/{merchant}/limits', [Admin\MerchantController::class, 'updateLimits'])->name('limits');
        Route::post('/{merchant}/payment-methods', [Admin\MerchantController::class, 'updatePaymentMethods'])->name('payment-methods');
        Route::post('/{merchant}/webhook-test', [Admin\MerchantController::class, 'testWebhook'])->name('webhook-test');
        Route::post('/{merchant}/rotate-secret', [Admin\MerchantController::class, 'rotateSecret'])->name('rotate-secret');
        Route::delete('/{merchant}', [Admin\MerchantController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('withdrawals')->name('withdrawals.')->group(function () {
        Route::get('/', [Admin\WithdrawalController::class, 'index'])->name('index');
        Route::post('/{withdrawal}/approve', [Admin\WithdrawalController::class, 'approve'])->name('approve');
        Route::post('/{withdrawal}/reject', [Admin\WithdrawalController::class, 'reject'])->name('reject');
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
    });

    Route::prefix('refunds')->name('refunds.')->group(function () {
        Route::get('/', [Admin\RefundController::class, 'index'])->name('index');
        Route::post('/{refund}/approve', [Admin\RefundController::class, 'approve'])->name('approve');
        Route::post('/{refund}/reject', [Admin\RefundController::class, 'reject'])->name('reject');
    });

    Route::prefix('currencies')->name('currencies.')->group(function () {
        Route::get('/', [Admin\CurrencyController::class, 'index'])->name('index');
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
// single-segment paths fall through to a published Page lookup.
Route::get('/{page:slug}', function (\App\Models\Page $page) {
    abort_unless($page->is_published, 404);

    return view('pages.show', compact('page'));
})->name('pages.show');
