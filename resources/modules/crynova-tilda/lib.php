<?php
/**
 * Shared helpers for the Crynova × Tilda bridge.
 */
function crynova_config(): array
{
    return require __DIR__ . '/config.php';
}

/** Create a Crynova invoice; returns the decoded response. */
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

/** Verify an incoming Crynova webhook signature against the raw body. */
function crynova_verify_webhook(string $payload): bool
{
    $cfg = crynova_config();
    $sig = $_SERVER['HTTP_X_CRYNOVA_SIG'] ?? '';
    $provided = strpos($sig, 'sha256=') === 0 ? substr($sig, 7) : $sig;
    $expected = hash_hmac('sha256', $payload, (string) $cfg['webhook_secret']);
    return $cfg['webhook_secret'] && hash_equals($expected, $provided);
}
