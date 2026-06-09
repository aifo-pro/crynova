<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\ApiIpListService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\IpUtils;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = $request->ip();

        if (! app(ApiIpListService::class)->allows($clientIp)) {
            return response()->json(['error' => 'IP not allowed by API ips.json.'], 403);
        }

        $raw = $request->bearerToken()
            ?? $request->header('X-Api-Key');

        // Never accept API keys via query/body — they end up in logs and referrer headers
        if (! $raw && ($request->query('api_key') || $request->input('api_key'))) {
            return response()->json(['error' => 'Pass API key via Authorization: Bearer or X-Api-Key header.'], 401);
        }

        if (! $raw) {
            return response()->json(['error' => 'API key required.'], 401);
        }

        $apiKey = ApiKey::findByRawKey($raw);

        if (! $apiKey) {
            return response()->json(['error' => 'Invalid or revoked API key.'], 401);
        }

        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return response()->json(['error' => 'API key has expired.'], 401);
        }

        $merchant = $apiKey->merchant;

        if (! $merchant || ! $merchant->is_active) {
            return response()->json(['error' => 'Merchant account is disabled.'], 403);
        }

        if (! $merchant->featuresUnlocked()) {
            return response()->json(['error' => 'Merchant is not approved for API access.'], 403);
        }

        // IP whitelist check
        if (! empty($apiKey->ip_whitelist)) {
            if (! IpUtils::checkIp($clientIp, $apiKey->ip_whitelist)) {
                return response()->json(['error' => 'IP not whitelisted.'], 403);
            }
        }

        $apiKey->touchQuietly('last_used_at');

        $request->merge(['_api_key' => $apiKey]);
        $request->setUserResolver(fn () => $apiKey->merchant->user);

        return $next($request);
    }
}
