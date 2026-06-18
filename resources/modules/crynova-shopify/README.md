# Crynova × Shopify

Accept crypto payments on Shopify via Crynova. Shopify does not allow custom
server-side payment gateways without a Payments Partner approval, so this is a
self-hosted PHP bridge that works with a **manual payment method**.

## Setup
1. Upload this folder to your PHP hosting.
2. Edit `config.php`:
   - Crynova `api_key`, `webhook_secret`, `api_base`.
   - Shopify `shop`, `admin_token` (Admin API access token with `write_orders`),
     and `shopify_webhook_secret`.
3. In Shopify: Settings → Payments → add a **manual payment method** named
   "Crynova (Crypto)".
4. Shopify → Settings → Notifications → Webhooks: create an **orders/create**
   webhook (JSON) pointing to `https://your-host/order-created.php`.
5. Add `https://your-host/crynova-webhook.php` to your Crynova project webhooks.

## Flow
- On a new order, `order-created.php` (Shopify webhook, HMAC-verified) creates a
  Crynova invoice and writes the `checkout_url` to the order note.
- `crynova-webhook.php` verifies the Crynova signature and, on `invoice.paid`,
  registers a paid transaction on the Shopify order via the Admin API.

Compatible with Crynova API v1.
