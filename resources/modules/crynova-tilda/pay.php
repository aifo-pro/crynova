<?php
/**
 * Crynova × Tilda — entry point.
 *
 * Point your Tilda payment button / form to this URL. It reads the order amount
 * and id, creates a Crynova invoice and redirects the customer to the hosted
 * crypto checkout.
 *
 * Accepted params (GET or POST): amount, orderid (or order_id), currency, email.
 */
require __DIR__ . '/lib.php';

$cfg = crynova_config();

$amount   = (float) ($_REQUEST['amount'] ?? 0);
$orderId  = (string) ($_REQUEST['orderid'] ?? $_REQUEST['order_id'] ?? ('tilda-' . time()));
$currency = strtoupper((string) ($_REQUEST['currency'] ?? 'USD'));
$email    = (string) ($_REQUEST['email'] ?? '');

if ($amount <= 0) {
    header('Location: ' . $cfg['fail_url']);
    exit;
}

$invoice = crynova_create_invoice([
    'currency'    => $currency,
    'amount'      => $amount,
    'order_id'    => $orderId,
    'description' => 'Tilda order ' . $orderId,
    'metadata'    => array_filter(['source' => 'tilda', 'email' => $email]),
]);

if (! empty($invoice['checkout_url'])) {
    header('Location: ' . $invoice['checkout_url']);
    exit;
}

header('Location: ' . $cfg['fail_url']);
