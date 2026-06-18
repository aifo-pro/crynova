<?php
/**
 * Shared helpers for the Crynova × GetCourse bridge.
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

/** Mark a GetCourse deal as paid via the deals API. */
function getcourse_mark_paid(string $orderId, ?string $email, float $amount): void
{
    $cfg = crynova_config();
    $params = base64_encode(json_encode([
        'user' => array_filter(['email' => $email]),
        'deal' => [
            'deal_number' => $orderId,
            'deal_status' => $cfg['gc_paid_status'],
            'deal_cost'   => $amount,
        ],
    ], JSON_UNESCAPED_UNICODE));

    $url = 'https://' . $cfg['gc_account'] . '.getcourse.ru/pl/api/deals';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_POSTFIELDS     => http_build_query([
            'action' => 'add',
            'key'    => $cfg['gc_secret'],
            'params' => $params,
        ]),
    ]);
    curl_exec($ch);
    curl_close($ch);
}
