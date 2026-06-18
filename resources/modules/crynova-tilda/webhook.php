<?php
/**
 * Crynova × Tilda — webhook receiver.
 * Add this URL to your Crynova project webhooks. Verifies the signature and
 * lets you trigger fulfilment (email, CRM, Tilda Members access, etc.).
 */
require __DIR__ . '/lib.php';

$payload = file_get_contents('php://input');

if (! crynova_verify_webhook($payload)) {
    http_response_code(400);
    exit('Invalid signature');
}

$event = json_decode($payload, true);
$data  = $event['data'] ?? $event;

if (($event['event'] ?? '') === 'invoice.paid') {
    // Payment confirmed for $data['order_id'] / $data['amount'] $data['currency'].
    // TODO: grant access / send confirmation email / push to your CRM here.
    // file_put_contents(__DIR__.'/paid.log', date('c')." {$data['order_id']}\n", FILE_APPEND);
}

http_response_code(200);
echo 'OK';
