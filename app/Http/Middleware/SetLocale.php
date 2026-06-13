<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** Locales the site is available in. */
    public const SUPPORTED = ['uk', 'en', 'pl'];

    public function handle(Request $request, Closure $next): Response
    {
        $defaultLocale = (string) Setting::get('default_site_language', config('app.locale', 'uk'));

        // Priority: explicit session choice → signed-in user preference →
        // auto-detected from the browser/region → site default.
        $locale = $this->normalize(
            $request->session()->get('locale')
            ?: $request->user()?->language
            ?: $this->detectFromRequest($request)
            ?: $defaultLocale
        );

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'uk';
        }

        App::setLocale($locale);

        return $next($request);
    }

    /** Map legacy/region codes to our supported locales. */
    private function normalize(?string $locale): string
    {
        $locale = strtolower((string) $locale);
        $locale = str_replace('_', '-', $locale);
        $locale = explode('-', $locale)[0]; // en-US → en, pl-PL → pl

        return $locale === 'ua' ? 'uk' : $locale;
    }

    /** Detect a preferred locale from the Accept-Language header (region-aware). */
    private function detectFromRequest(Request $request): ?string
    {
        $header = (string) $request->server('HTTP_ACCEPT_LANGUAGE', '');
        if ($header === '') {
            return null;
        }

        $langs = [];
        foreach (explode(',', $header) as $part) {
            $bits = explode(';q=', trim($part));
            $code = $this->normalize($bits[0] ?? '');
            $q = isset($bits[1]) ? (float) $bits[1] : 1.0;
            if ($code !== '') {
                $langs[$code] = max($langs[$code] ?? 0, $q);
            }
        }
        arsort($langs);

        foreach (array_keys($langs) as $code) {
            if (in_array($code, self::SUPPORTED, true)) {
                return $code;
            }
        }

        return null;
    }
}
