<?php

use Illuminate\Support\Facades\Route as RouteFacade;

if (! function_exists('lroute')) {
    /**
     * Locale-aware URL for a public route.
     *
     * uk (default) → unprefixed path (/pricing); en/pl → prefixed (/en/pricing).
     * Mirrors route() but prepends the active locale segment for non-default
     * locales so links keep the visitor inside their language.
     */
    function lroute(string $name, array $params = [], bool $absolute = true): string
    {
        $path = route($name, $params, false); // relative uk path, e.g. /pricing
        $locale = app()->getLocale();

        if (in_array($locale, ['en', 'pl', 'ru'], true)) {
            $path = '/' . $locale . ($path === '/' ? '' : $path);
        }

        return $absolute ? url($path) : $path;
    }
}

if (! function_exists('locale_path')) {
    /**
     * The current request path with a given locale prefix applied (or stripped
     * for the default locale). Used for hreflang tags and the language switcher.
     */
    function locale_path(string $locale, ?string $path = null): string
    {
        $path = $path ?? request()->getPathInfo();
        // Strip any existing locale prefix.
        $path = preg_replace('#^/(en|pl|ru)(?=/|$)#', '', $path);
        if ($path === '' || $path === false) {
            $path = '/';
        }

        if (in_array($locale, ['en', 'pl', 'ru'], true)) {
            $path = '/' . $locale . ($path === '/' ? '' : $path);
        }

        return url($path);
    }
}
