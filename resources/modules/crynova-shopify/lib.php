<?php
/**
 * Shared helpers for the Crynova × Shopify bridge.
 */
function crynova_config(): array
{
    return require __DIR__ . '/config.php';
}

function crynova_create_invoice(array $params): array
{
    $cfg = crynova_config();
    $ch = curl_init(rtrim($cfg['api_base'], '/') . '/invoices');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_POSTFIELDS     => json_encode($params),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $cfg['api_key'],
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    $decoded = json_decode((string) $raw, true);
    return is_array($decoded) ? $decoded : [];
}

function crynova_verify_webhook(string $payload): bool
{
    $cfg = crynova_config();
    $sig = $_SERVER['HTTP_X_CRYNOVA_SIG'] ?? '';
    $provided = strpos($sig, 'sha256=') === 0 ? substr($sig, 7) : $sig;
    $expected = hash_hmac('sha256', $payload, (string) $cfg['webhook_secret']);
    return $cfg['webhook_secret'] && hash_equals($expected, $provided);
}

/** Verify a Shopify webhook (base64 HMAC-SHA256 in X-Shopify-Hmac-Sha256). */
function shopify_verify_webhook(string $payload): bool
{
    $cfg = crynova_config();
    $hmac = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] ?? '';
    $expected = base64_encode(hash_hmac('sha256', $payload, (string) $cfg['shopify_webhook_secret'], true));
    return $cfg['shopify_webhook_secret'] && hash_equals($expected, $hmac);
}

/** Call the Shopify Admin REST API. */
function shopify_api(string $method, string $path, ?array $body = null): array
{
    $cfg = crynova_config();
    $url = 'https://' . $cfg['shop'] . '/admin/api/' . $cfg['shopify_api_ver'] . $path;
    $ch = curl_init($url);
    $headers = [
        'X-Shopify-Access-Token: ' . $cfg['admin_token'],
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw = curl_exec($ch);
    curl_close($ch);
    $decoded = json_decode((string) $raw, true);
    return is_array($decoded) ? $decoded : [];
}
