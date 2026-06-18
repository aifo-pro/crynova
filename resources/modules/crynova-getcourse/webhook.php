<?php
/**
 * Crynova × GetCourse — webhook receiver.
 * Add this URL to your Crynova project webhooks. On payment it marks the
 * matching GetCourse deal as paid via the deals API.
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
    $email = $data['metadata']['email'] ?? null;
    getcourse_mark_paid((string) ($data['order_id'] ?? ''), $email, (float) ($data['amount'] ?? 0));
}

http_response_code(200);
echo 'OK';
