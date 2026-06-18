<?php
/**
 * Crynova × Shopify — Shopify "orders/create" webhook receiver.
 *
 * When a new order is placed with the manual "Crynova" payment method, this
 * creates a Crynova invoice and stores the checkout link on the order note so
 * the customer can pay in crypto.
 */
require __DIR__ . '/lib.php';

$payload = file_get_contents('php://input');

if (! shopify_verify_webhook($payload)) {
    http_response_code(401);
    exit('Invalid Shopify signature');
}

$order = json_decode($payload, true);
if (empty($order['id'])) {
    http_response_code(400);
    exit('No order');
}

$invoice = crynova_create_invoice([
    'currency'    => $order['currency'] ?? 'USD',
    'amount'      => (float) ($order['total_price'] ?? 0),
    'order_id'    => (string) $order['id'],
    'description' => 'Shopify order ' . ($order['name'] ?? $order['id']),
    'metadata'    => array_filter(['source' => 'shopify', 'email' => $order['email'] ?? null]),
]);

if (! empty($invoice['checkout_url'])) {
    // Surface the pay link on the order so staff/customer can access it.
    shopify_api('PUT', '/orders/' . $order['id'] . '.json', [
        'order' => [
            'id'   => $order['id'],
            'note' => 'Crynova crypto checkout: ' . $invoice['checkout_url'],
        ],
    ]);
}

http_response_code(200);
echo 'OK';
