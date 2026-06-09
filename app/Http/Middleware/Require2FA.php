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

        if ($user && $user->google2fa_enabled && ! $request->session()->get('2fa_verified')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => '2FA verification required.'], 403);
            }

            return redirect()->route('2fa.verify');
        }

        return $next($request);
    }
}
