<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $defaultLocale = (string) Setting::get('default_site_language', config('app.locale', 'uk'));
        $userLocale = $request->user()?->language;
        $locale = $request->session()->get('locale', $userLocale ?: $defaultLocale);

        if ($locale === 'ua') {
            $locale = 'uk';
        }

        if (! in_array($locale, ['uk', 'en'], true)) {
            $locale = 'uk';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
