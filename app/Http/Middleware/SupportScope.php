<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tech-support agents (role "support") work in a focused subset of the admin
 * panel: tickets, reply templates, contact messages, search and the header
 * notification feed. Everything else is out of scope. Full admins are unaffected.
 */
class SupportScope
{
    /** Route-name prefixes a support agent may access. */
    private const ALLOWED = [
        'admin.support.',
        'admin.templates.',
        'admin.contact.',
        'admin.notifications.',
        'admin.search',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isSupport()) {
            return $next($request);
        }

        $routeName = (string) optional($request->route())->getName();

        foreach (self::ALLOWED as $prefix) {
            if ($routeName === rtrim($prefix, '.') || str_starts_with($routeName, $prefix)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Support role: out of scope.'], 403);
        }

        return redirect()->route('admin.support.index')
            ->with('error', 'Цей розділ доступний лише повним адміністраторам.');
    }
}
