<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Read-only admins (role "support") may view every admin screen but cannot
 * perform any state-changing request. Full admins (role "admin") are unaffected.
 */
class PreventReadonlyWrite
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isSupport() && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Read-only admin: action not allowed.'], 403);
            }

            return back()->with('error', 'Режим лише для перегляду — дія недоступна.');
        }

        return $next($request);
    }
}
