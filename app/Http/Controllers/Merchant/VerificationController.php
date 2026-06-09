<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/*
 * Domain / Telegram ownership verification (AIFO-style).
 * Methods:
 *   - file:     a TXT file at https://domain/{code}.txt containing the code
 *   - homepage: the code present anywhere in the homepage HTML
 *   - dns:      a DNS TXT record containing the code
 *   - telegram: the code present in the public t.me/{channel} page
 * On success the merchant moves to "moderation" (awaiting admin approval).
 */
class VerificationController extends Controller
{
    public function index(Request $request, Merchant $merchant)
    {
        // Already past verification — nothing to do here
        if (! $merchant->isUnverified() && ! $merchant->isRejected()) {
            return redirect()->route('merchant.control', $merchant);
        }

        $code = $merchant->ensureVerificationCode();

        $verification = $this->buildVerificationData($merchant, $code);

        return view('merchant.verification', compact('merchant', 'verification', 'code'));
    }

    public function verify(Request $request, Merchant $merchant)
    {
        $request->validate([
            'method' => ['required', 'in:file,homepage,dns,telegram'],
        ]);

        $method = $request->input('method');
        $code   = $merchant->ensureVerificationCode();

        // Validate method matches merchant type
        if ($merchant->merchant_type === 'telegram' && $method !== 'telegram') {
            return back()->with('error', __('merchant.telegram_method_required'));
        }
        if ($merchant->merchant_type === 'domain' && $method === 'telegram') {
            return back()->with('error', __('merchant.domain_method_required'));
        }

        $result = match ($method) {
            'file'     => $this->verifyFile($merchant, $code),
            'homepage' => $this->verifyHomepage($merchant, $code),
            'dns'      => $this->verifyDns($merchant, $code),
            'telegram' => $this->verifyTelegram($merchant, $code),
        };

        if (! $result['ok']) {
            return back()->with('error', $result['message']);
        }

        // Verified → move to moderation
        $merchant->update([
            'status'              => Merchant::STATUS_MODERATION,
            'verification_method' => $method,
            'verified_at'         => now(),
            'reject_reason'       => null,
        ]);

        AuditLog::record('merchant.verified', $merchant, [], ['method' => $method]);

        return redirect()
            ->route('merchant.control', $merchant)
            ->with('success', __('merchant.ownership_verified'));
    }

    // ── Verification data for the view ─────────────────────────────
    private function buildVerificationData(Merchant $merchant, string $code): array
    {
        $domain = $merchant->domain;

        return [
            'file_name'     => "{$code}.txt",
            'file_content'  => $code,
            'file_url'      => $domain ? "https://{$domain}/{$code}.txt" : null,
            'homepage_code' => "<!-- {$code} -->",
            'dns_record'    => $code,
            'dns_host'      => $domain,
            'telegram_code' => $code,
        ];
    }

    // ── Verification strategies ────────────────────────────────────
    private function verifyFile(Merchant $merchant, string $code): array
    {
        // Try https first, then http (some domains don't have valid TLS yet).
        foreach (["https://{$merchant->domain}/{$code}.txt", "http://{$merchant->domain}/{$code}.txt"] as $url) {
            $body = $this->fetch($url);
            if ($body !== null && str_contains(trim($body), $code)) {
                return ['ok' => true];
            }
        }

        return ['ok' => false, 'message' => __('merchant.file_not_found', ['url' => "https://{$merchant->domain}/{$code}.txt"])];
    }

    private function verifyHomepage(Merchant $merchant, string $code): array
    {
        foreach (["https://{$merchant->domain}", "http://{$merchant->domain}"] as $url) {
            $body = $this->fetch($url);
            if ($body !== null && str_contains($body, $code)) {
                return ['ok' => true];
            }
        }

        return ['ok' => false, 'message' => __('merchant.homepage_code_not_found')];
    }

    /**
     * Fetch a URL robustly for ownership verification.
     *
     * Note: TLS verification is intentionally relaxed here. The trust anchor for
     * domain verification is the secret code the owner places on their site — not
     * the validity of their TLS certificate. This also avoids false negatives from
     * missing CA bundles on the server and self-signed/new certificates on the
     * merchant's domain. A browser-like User-Agent is sent because many hosts block
     * unknown clients.
     */
    private function fetch(string $url): ?string
    {
        try {
            $res = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; CrynovaVerify/1.0; +https://crynova.io)',
                    'Accept'     => 'text/plain, text/html, */*',
                ])
                ->withoutVerifying()   // see method docblock
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->timeout(12)
                ->get($url);

            return $res->successful() ? $res->body() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function verifyDns(Merchant $merchant, string $code): array
    {
        $records = @dns_get_record($merchant->domain, DNS_TXT) ?: [];
        foreach ($records as $record) {
            if (isset($record['txt']) && str_contains($record['txt'], $code)) {
                return ['ok' => true];
            }
        }

        return ['ok' => false, 'message' => __('merchant.dns_not_found')];
    }

    private function verifyTelegram(Merchant $merchant, string $code): array
    {
        $body = $this->fetch("https://t.me/{$merchant->telegram_channel}");
        if ($body !== null && str_contains($body, $code)) {
            return ['ok' => true];
        }

        return ['ok' => false, 'message' => __('merchant.telegram_code_not_found')];
    }
}
