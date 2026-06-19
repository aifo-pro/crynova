# Crynova for Flute CMS

Accept cryptocurrency payments (BTC, ETH, USDT, TRX, LTC, DOGE) in Flute CMS via
the Crynova payment gateway. Built as a Flute module on top of the Omnipay-based
payment system, so creation, redirect, callback and verification are handled by
the Flute core.

## Structure
```
Crynova/
├── module.json
├── Providers/CrynovaProvider.php        # registers the driver
├── Drivers/CrynovaDriver.php            # Flute payment driver (adapter → Omnipay gateway)
├── Gateway/CrynovaGateway.php           # Omnipay gateway
├── Gateway/Message/                     # purchase + completePurchase requests/responses
└── Resources/views/settings.blade.php   # admin settings form
```

## Installation
1. Upload the module archive in **Admin Panel → Modules** (or drop the `Crynova`
   folder into `app/Modules/`).
2. Activate **Crynova** in the Modules list.
3. Go to **Admin Panel → Payment Systems → Add**, choose **Crynova**.
4. Fill in **API Key** and **Webhook Secret** (and API Base URL for self-hosted).
5. Copy the **Notification URL** shown by Flute into your Crynova project webhooks.

## How it works
- `CrynovaDriver` registers with `PaymentDriverFactory` via the
  `RegisterPaymentFactoriesEvent`. Its adapter is the Omnipay `CrynovaGateway`.
- `purchase()` creates an invoice (`POST /api/v1/invoices`) and returns a redirect
  to the hosted `checkout_url`.
- `completePurchase()` verifies the `X-Crynova-Sig` HMAC-SHA256 signature and
  marks the payment successful on `invoice.paid`.

Compatible with Flute CMS modules API and Crynova API v1.
