<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Require2FA
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->isAdmin() && ! $user->google2fa_enabled) {
            if (! $request->routeIs('2fa.*', 'logout')) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => '2FA must be enabled for admin accounts.'], 403);
                }

                return redirect()->route('2fa.setup')
                    ->with('error', 'Увімкніть двофакторну автентифікацію для доступу до адмін-панелі.');
            }

            return $next($request);
        }

        if ($user->google2fa_enabled && ! $request->session()->get('2fa_verified')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => '2FA verification required.'], 403);
            }

            return redirect()->route('2fa.verify');
        }

        return $next($request);
    }
}
