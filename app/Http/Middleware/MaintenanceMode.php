<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) Setting::get('maintenance_mode', false)) {
            return $next($request);
        }

        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        return response()->view('maintenance', [
            'message' => Setting::get('maintenance_message', 'Сайт тимчасово не працює.'),
            'siteName' => Setting::get('site_name', 'Crynova'),
        ], 503);
    }

    private function shouldBypass(Request $request): bool
    {
        if ($request->is('admin') || $request->is('admin/*')) {
            return true;
        }

        if ($request->is('build/*')
            || $request->is('assets/*')
            || $request->is('favicon.ico')
            || $request->is('robots.txt')
            || $request->is('sitemap.xml')
            || $request->is('up')
        ) {
            return true;
        }

        if ($request->routeIs('login', 'logout', 'password.*', '2fa.*', 'verification.*', 'locale.switch', 'newsletter.unsubscribe')) {
            return true;
        }

        $user = $request->user();

        return $user && method_exists($user, 'isAdmin') && $user->isAdmin();
    }
}
