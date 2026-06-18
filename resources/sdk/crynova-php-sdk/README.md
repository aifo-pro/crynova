# Crynova PHP SDK

Official PHP SDK for the [Crynova](https://crynova.io) crypto payment gateway.
Create invoices, check payment status and verify webhooks in a few lines — no
external dependencies (uses `ext-curl` only).

## Requirements

- PHP 8.1+
- `ext-curl`, `ext-json`

## Installation

### With Composer (local path)

Unzip the SDK, then from your project:

```bash
composer config repositories.crynova path ./crynova-php-sdk
composer require crynova/crynova-php-sdk:@dev
```

### Without Composer

```php
require __DIR__ . '/crynova-php-sdk/src/CrynovaException.php';
require __DIR__ . '/crynova-php-sdk/src/CrynovaClient.php';
require __DIR__ . '/crynova-php-sdk/src/Webhook.php';
```

## Quick start

```php
use Crynova\CrynovaClient;

$crynova = new CrynovaClient('sk_live_your_api_key');

// Create an invoice (fiat-priced — customer picks the crypto at checkout)
$invoice = $crynova->createInvoice([
    'currency'    => 'USD',
    'amount'      => 49.90,
    'order_id'    => 'ORDER-1024',
    'description' => 'Pro plan — 1 month',
    'expires_in'  => 60, // minutes
], idempotencyKey: 'ORDER-1024'); // optional, safe retries

header('Location: ' . $invoice['checkout_url']);
```

Get your API key in **Project → Integration → API keys**.

## API methods

```php
$crynova->currencies();                 // available currencies & networks
$crynova->createInvoice($params, $key); // create an invoice
$crynova->listInvoices($filters);       // status, order_id, currency, per_page
$crynova->getInvoice($uuid);            // full invoice details
$crynova->invoiceStatus($uuid);         // lightweight status + confirmations
$crynova->cancelInvoice($uuid);         // cancel an unpaid invoice
```

### Create invoice parameters

| Field         | Type    | Notes                                                        |
|---------------|---------|--------------------------------------------------------------|
| `currency`    | string  | Fiat (`USD`, `UAH`, …) or crypto (`BTC`, `USDT_TRC20`, …)    |
| `amount`      | number  | Required, > 0                                                |
| `order_id`    | string  | Your internal order reference                                |
| `description` | string  | Shown on the checkout page                                   |
| `expires_in`  | int     | Minutes until expiry (5–1440)                                |
| `metadata`    | array   | Key/value strings echoed back in webhooks                    |

## Webhooks

Crynova signs every webhook with HMAC-SHA256 over the raw body. Verify it
before trusting the payload:

```php
use Crynova\Webhook;

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_CRYNOVA_SIG'] ?? '';

try {
    $event = Webhook::parse($payload, $signature, $webhookSecret);
} catch (\Crynova\CrynovaException $e) {
    http_response_code(400);
    exit('Invalid signature');
}

switch ($event['event']) {
    case 'invoice.paid':
        // fulfil the order
        break;
    case 'invoice.expired':
        // …
        break;
}

http_response_code(200);
```

Events: `invoice.created`, `invoice.waiting`, `invoice.paid`,
`invoice.underpaid`, `invoice.expired`.

## Error handling

Any non-2xx response throws `Crynova\CrynovaException`:

```php
try {
    $invoice = $crynova->getInvoice($uuid);
} catch (\Crynova\CrynovaException $e) {
    echo $e->getMessage();      // human-readable error
    echo $e->getHttpStatus();   // 401, 404, 422, …
    print_r($e->response);      // raw error body
}
```

## License

MIT
