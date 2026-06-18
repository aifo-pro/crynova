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

    /** Public content routes whose language is determined by the URL, not the session. */
    private const LOCALIZED_ROUTES = [
        'home', 'pricing', 'coins', 'developers', 'api.docs', 'api.sdk',
        'contact', 'contact.store', 'blog', 'blog.show', 'blog.rate', 'pages.show',
        'legal.terms', 'legal.privacy', 'legal.aml-kyc', 'legal.risk-disclosure',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $defaultLocale = 'uk'; // the unprefixed root language

        $prefix = $request->segment(1);

        if (in_array($prefix, ['en', 'pl'], true)) {
            // Localized URL (/en/…, /pl/…) — the prefix is authoritative.
            $locale = $prefix;
        } elseif (in_array($request->route()?->getName(), self::LOCALIZED_ROUTES, true)) {
            // Unprefixed public page — always the default language (deterministic for SEO).
            $locale = $defaultLocale;
        } else {
            // Cabinet / auth / checkout — personal preference:
            // explicit session choice → user setting → browser → default.
            $configured = (string) Setting::get('default_site_language', config('app.locale', 'uk'));
            $locale = $this->normalize(
                $request->session()->get('locale')
                ?: $request->user()?->language
                ?: $this->detectFromRequest($request)
                ?: $configured
            );
        }

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
