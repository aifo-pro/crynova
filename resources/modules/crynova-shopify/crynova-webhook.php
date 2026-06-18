<?php
/**
 * Crynova × Shopify — Crynova webhook receiver.
 * On invoice.paid, registers a paid transaction on the Shopify order so it is
 * marked as paid in the Shopify admin.
 */
require __DIR__ . '/lib.php';

$payload = file_get_contents('php://input');

if (! crynova_verify_webhook($payload)) {
    http_response_code(400);
    exit('Invalid signature');
}

$event = json_decode($payload, true);
$data  = $event['data'] ?? $event;
$orderId = $data['order_id'] ?? null;

if ($orderId && ($event['event'] ?? '') === 'invoice.paid') {
    shopify_api('POST', '/orders/' . $orderId . '/transactions.json', [
        'transaction' => [
            'kind'     => 'sale',
            'status'   => 'success',
            'amount'   => (string) ($data['amount_received'] ?? $data['amount'] ?? ''),
            'currency' => $data['price_currency'] ?? null,
            'gateway'  => 'Crynova',
        ],
    ]);
}

http_response_code(200);
echo 'OK';
