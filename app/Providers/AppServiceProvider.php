<?php

namespace App\Providers;

use App\Models\ApiKey;
use App\Policies\ApiKeyPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(ApiKey::class, ApiKeyPolicy::class);

        View::composer('layouts.app', \App\View\Composers\HeaderUserComposer::class);

        RateLimiter::for('api', function (Request $request) {
            $apiKey = $request->get('_api_key');

            return Limit::perMinute(60)
                ->by($apiKey?->id ?? $request->ip())
                ->response(fn () => response()->json(['error' => 'Too many requests.'], 429));
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
