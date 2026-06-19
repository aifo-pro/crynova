<?php

namespace Crynova;

/**
 * Crynova API client.
 *
 * <code>
 * $crynova = new \Crynova\CrynovaClient('sk_live_xxx');
 *
 * $invoice = $crynova->createInvoice([
 *     'currency'    => 'USD',          // fiat (customer picks crypto) or a crypto code (BTC, USDT_TRC20, …)
 *     'amount'      => 49.90,
 *     'order_id'    => 'ORDER-1024',
 *     'description' => 'Pro plan — 1 month',
 *     'expires_in'  => 60,             // minutes (5–1440)
 * ]);
 *
 * echo $invoice['checkout_url'];       // redirect your customer here
 * </code>
 *
 * No external dependencies — uses ext-curl only.
 */
class CrynovaClient
{
    public const VERSION = '1.0.0';

    private string $apiKey;
    private string $baseUrl;
    private int $timeout;

    /**
     * @param string $apiKey  Your merchant API key (Project → Integration → API keys).
     * @param string $baseUrl API base URL. Override only for self-hosted instances.
     * @param int    $timeout Request timeout in seconds.
     */
    public function __construct(string $apiKey, string $baseUrl = 'https://crynova.io/api/v1', int $timeout = 30)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /** List currencies and networks available to your account. */
    public function currencies(): array
    {
        return $this->request('GET', '/currencies');
    }

    /** Merchant balances per currency (available, locked, total). */
    public function balance(): array
    {
        return $this->request('GET', '/balance');
    }

    /**
     * Create a payment invoice.
     *
     * @param array       $params         currency, amount, order_id, description, expires_in, metadata
     * @param string|null $idempotencyKey Pass a unique key to safely retry without creating duplicates.
     */
    public function createInvoice(array $params, ?string $idempotencyKey = null): array
    {
        $headers = $idempotencyKey ? ['Idempotency-Key: ' . $idempotencyKey] : [];

        return $this->request('POST', '/invoices', $params, $headers);
    }

    /**
     * List invoices.
     *
     * @param array $filters status, order_id, currency, per_page
     */
    public function listInvoices(array $filters = []): array
    {
        return $this->request('GET', '/invoices', $filters);
    }

    /** Full invoice details by its UUID. */
    public function getInvoice(string $uuid): array
    {
        return $this->request('GET', '/invoices/' . rawurlencode($uuid));
    }

    /** Lightweight status + confirmations check (ideal for polling). */
    public function invoiceStatus(string $uuid): array
    {
        return $this->request('GET', '/invoices/' . rawurlencode($uuid) . '/status');
    }

    /** Cancel an unpaid invoice. */
    public function cancelInvoice(string $uuid): array
    {
        return $this->request('POST', '/invoices/' . rawurlencode($uuid) . '/cancel');
    }

    /**
     * Perform an authenticated API request.
     *
     * @throws CrynovaException on transport errors or non-2xx responses.
     */
    public function request(string $method, string $path, array $params = [], array $extraHeaders = []): array
    {
        $method = strtoupper($method);
        $url = $this->baseUrl . $path;

        $headers = array_merge([
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
            'User-Agent: crynova-php-sdk/' . self::VERSION,
        ], $extraHeaders);

        $ch = curl_init();

        if ($method === 'GET') {
            if ($params) {
                $url .= '?' . http_build_query($params);
            }
        } else {
            $body = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        $raw = curl_exec($ch);

        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new CrynovaException('Request failed: ' . $err);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            $decoded = [];
        }

        if ($status < 200 || $status >= 300) {
            $message = $decoded['error'] ?? $decoded['message'] ?? ('HTTP ' . $status);
            throw new CrynovaException($message, $status, $decoded);
        }

        return $decoded;
    }
}
