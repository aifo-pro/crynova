<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Full admins and read-only admins (role "support") may enter the panel.
        if (! $user || ! ($user->isAdmin() || $user->isSupport())) {
            abort(403);
        }

        return $next($request);
    }
}
