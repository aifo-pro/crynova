<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
@php
    // Build an absolute localized URL from a uk-relative path.
    $mk = fn (string $locale, string $path) => url($locale === 'uk' ? $path : '/' . $locale . ($path === '/' ? '' : $path));
@endphp
@foreach($entries as $e)
@foreach(['uk', 'en', 'pl', 'ru'] as $loc)
    <url>
        <loc>{{ $mk($loc, $e['path']) }}</loc>
        <xhtml:link rel="alternate" hreflang="uk" href="{{ $mk('uk', $e['path']) }}"/>
        <xhtml:link rel="alternate" hreflang="en" href="{{ $mk('en', $e['path']) }}"/>
        <xhtml:link rel="alternate" hreflang="pl" href="{{ $mk('pl', $e['path']) }}"/>
        <xhtml:link rel="alternate" hreflang="ru" href="{{ $mk('ru', $e['path']) }}"/>
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $mk('uk', $e['path']) }}"/>
        @isset($e['lastmod'])<lastmod>{{ $e['lastmod'] }}</lastmod>@endisset
        <changefreq>{{ $e['freq'] }}</changefreq>
        <priority>{{ $e['priority'] }}</priority>
    </url>
@endforeach
@endforeach
</urlset>
