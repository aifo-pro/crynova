<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\MaintenanceMode::class,
            \App\Http\Middleware\EnsureEmailVerifiedWhenRequired::class,
        ]);

        $middleware->alias([
            'require2fa'      => \App\Http\Middleware\Require2FA::class,
            'api.key'         => \App\Http\Middleware\AuthenticateApiKey::class,
            'merchant.owner'  => \App\Http\Middleware\EnsureMerchantOwner::class,
            'merchant.active' => \App\Http\Middleware\EnsureMerchantActive::class,
            'admin'           => \App\Http\Middleware\EnsureAdmin::class,
        ]);

        $middleware->trustProxies(at: '*');

        // Global rate limit for API
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
