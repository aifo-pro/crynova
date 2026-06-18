<?php
/**
 * Crynova × GetCourse — payment entry point.
 *
 * Set this URL as the "Payment URL" of a custom payment system in GetCourse.
 * GetCourse opens it with the deal number and amount; we create a Crynova
 * invoice and redirect the buyer to the crypto checkout.
 *
 * Params (GET/POST): order_id / deal_number, amount, email, currency.
 */
require __DIR__ . '/lib.php';

$cfg = crynova_config();

$orderId  = (string) ($_REQUEST['order_id'] ?? $_REQUEST['deal_number'] ?? $_REQUEST['id'] ?? ('gc-' . time()));
$amount   = (float) ($_REQUEST['amount'] ?? $_REQUEST['deal_cost'] ?? 0);
$email    = (string) ($_REQUEST['email'] ?? $_REQUEST['user'] ?? '');
$currency = strtoupper((string) ($_REQUEST['currency'] ?? 'USD'));

if ($amount <= 0) {
    header('Location: ' . $cfg['fail_url']);
    exit;
}

$invoice = crynova_create_invoice([
    'currency'    => $currency,
    'amount'      => $amount,
    'order_id'    => $orderId,
    'description' => 'GetCourse deal ' . $orderId,
    'metadata'    => array_filter(['source' => 'getcourse', 'email' => $email]),
]);

if (! empty($invoice['checkout_url'])) {
    header('Location: ' . $invoice['checkout_url']);
    exit;
}

header('Location: ' . $cfg['fail_url']);
