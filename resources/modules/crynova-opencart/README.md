# Crynova for OpenCart 3.x

Accept cryptocurrency payments (BTC, ETH, USDT, TRX, LTC, DOGE) in OpenCart via
the Crynova payment gateway.

## Installation

1. Zip the contents of this folder (so `upload/` and `install.xml` are at the
   archive root) or upload this archive directly.
2. Admin → Extensions → **Installer** → upload the ZIP.
3. Admin → Extensions → **Extensions** → choose **Payments** → install & edit
   **Crynova (Crypto payments)**.
4. Fill in:
   - **API Key** — Crynova dashboard: Project → Integration → API keys.
   - **Webhook Secret** — your project webhook secret.
   - **Paid order status** — e.g. *Processing*.
5. Copy the **Webhook URL** shown in settings
   (`https://your-shop/index.php?route=extension/payment/crynova/callback`)
   and add it to your Crynova project webhooks.

## How it works

* On checkout, `confirm()` creates an invoice via `POST /api/v1/invoices` and
  redirects the customer to the hosted Crynova `checkout_url`.
* The `callback()` endpoint verifies the `X-Crynova-Sig` HMAC-SHA256 signature
  and updates the order on `invoice.paid` / `invoice.expired`.

Compatible with Crynova API v1.
