<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerifiedWhenRequired
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) Setting::get('email_verification_enabled', false)) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || $user->isAdmin() || $user->email_verified_at) {
            return $next($request);
        }

        if ($request->routeIs('verification.*', 'logout')
            || $request->is('build/*')
            || $request->is('assets/*')
            || $request->is('favicon.ico')
        ) {
            return $next($request);
        }

        return redirect()->route('verification.notice');
    }
}
