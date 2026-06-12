<?php

namespace App\Support;

class UrlSafety
{
  public static function isPublicHttpUrl(string $url): bool
  {
    $parts = parse_url($url);

    if (! is_array($parts)) {
      return false;
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if (! in_array($scheme, ['http', 'https'], true)) {
      return false;
    }

    $host = strtolower((string) ($parts['host'] ?? ''));
    if ($host === '') {
      return false;
    }

    if (self::isBlockedHost($host)) {
      return false;
    }

    if (filter_var($host, FILTER_VALIDATE_IP)) {
      return self::isPublicIp($host);
    }

    $ips = @gethostbynamel($host);
    if ($ips === false || $ips === []) {
      $resolved = @gethostbyname($host);
      if ($resolved === $host) {
        return false;
      }
      $ips = [$resolved];
    }

    foreach ($ips as $ip) {
      if (! self::isPublicIp($ip)) {
        return false;
      }
    }

    return true;
  }

  private static function isBlockedHost(string $host): bool
  {
    if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
      return true;
    }

    return in_array($host, [
      'metadata.google.internal',
      'metadata.goog',
    ], true);
  }

  private static function isPublicIp(string $ip): bool
  {
    return filter_var(
      $ip,
      FILTER_VALIDATE_IP,
      FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
    ) !== false;
  }
}
