<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tech-support agents (role "support") can VIEW the diagnostic areas of the
 * admin panel (users, merchants, invoices, transactions, wallets, search) so
 * they can actually help with payment and merchant questions — but they may
 * only WRITE within the support scope (tickets, templates, contact messages,
 * and the harmless invoice re-check). Full admins are unaffected.
 */
class SupportScope
{
    /** Sections a support agent may not even open (secrets / config / financial ops). */
    private const READ_BLOCKED = [
        'admin.settings.',
        'admin.audit-logs.',
        'admin.newsletter.',
        'admin.currencies.',
        'admin.blog.',
        'admin.pages.',
        'admin.modules.',
        'admin.aml.',
        'admin.support-departments.',
    ];

    /** State-changing routes a support agent IS allowed to call. */
    private const WRITE_ALLOWED = [
        'admin.support.',
        'admin.templates.',
        'admin.contact.',
        'admin.notifications.',
        'admin.invoices.recheck',
        // Support may block/unblock users (abuse handling) but NOT change roles,
        // reset passwords, impersonate, or delete accounts.
        'admin.users.block',
        'admin.users.unblock',
        'admin.users.notes', // internal notes/tags — harmless, helps agents
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isSupport()) {
            return $next($request);
        }

        $routeName = (string) optional($request->route())->getName();
        $isWrite = ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);

        if ($isWrite) {
            if ($this->matches($routeName, self::WRITE_ALLOWED)) {
                return $next($request);
            }

            return $this->deny($request, 'Ця дія доступна лише повним адміністраторам. Ви можете переглядати дані, але не змінювати.');
        }

        // Read request.
        if ($this->matches($routeName, self::READ_BLOCKED)) {
            return $this->deny($request, 'Цей розділ доступний лише повним адміністраторам.');
        }

        return $next($request);
    }

    private function matches(string $routeName, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if ($routeName === rtrim($prefix, '.') || str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->route('admin.support.index')->with('error', $message);
    }
}
