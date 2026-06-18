# Crynova for PrestaShop 1.7 / 8.x

Accept crypto payments (BTC, ETH, USDT, TRX, LTC, DOGE) in PrestaShop via Crynova.

## Installation
1. Zip this folder as `crynovapay.zip` (the `crynovapay.php` must be at the
   archive root, inside a `crynovapay/` folder).
2. Admin → Modules → **Upload a module** → upload the ZIP.
3. Configure the module: **API Key**, **Webhook Secret**, **API Base URL**.
4. Copy the **Webhook URL** shown in settings into your Crynova project webhooks.

## How it works
- The `redirect` front controller creates the PrestaShop order, calls
  `POST /api/v1/invoices` and redirects the buyer to `checkout_url`.
- The `webhook` front controller verifies the `X-Crynova-Sig` signature and sets
  the order to **Payment accepted** on `invoice.paid`.

Compatible with Crynova API v1.
