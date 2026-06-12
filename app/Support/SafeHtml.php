<?php

namespace App\Support;

class SafeHtml
{
  private const ALLOWED_TAGS = '<p><br><strong><em><b><i><ul><ol><li><h1><h2><h3><h4><h5><h6><a><blockquote><code><pre><table><thead><tbody><tr><th><td><img><hr><span><div>';

  public static function clean(?string $html): string
  {
    if ($html === null || $html === '') {
      return '';
    }

    $html = strip_tags($html, self::ALLOWED_TAGS);
    $html = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
    $html = preg_replace('/javascript\s*:/i', '', $html) ?? $html;
    $html = preg_replace('/\s(src|href)\s*=\s*["\']\s*data:/i', ' $1="', $html) ?? $html;

    return $html;
  }
}
