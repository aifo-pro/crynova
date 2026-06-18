=== Crynova for WooCommerce ===
Contributors: crynova
Tags: crypto, payments, bitcoin, usdt, ethereum, woocommerce
Requires at least: 5.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: MIT

Accept cryptocurrency payments in WooCommerce via the Crynova payment gateway.

== Description ==

Crynova lets your WooCommerce store accept BTC, ETH, USDT (TRC-20/ERC-20/BEP-20),
TRX, LTC and DOGE. On checkout the customer is redirected to a hosted Crynova
payment page; once the payment is confirmed on-chain, a signed webhook marks the
order as paid automatically.

== Installation ==

1. Upload the `crynova-woocommerce` folder to `/wp-content/plugins/`, or install
   the ZIP via Plugins → Add New → Upload Plugin.
2. Activate "Crynova for WooCommerce".
3. Go to WooCommerce → Settings → Payments → Crynova.
4. Enable it and fill in:
   - API Key — from Crynova dashboard: Project → Integration → API keys.
   - Webhook Secret — your project webhook secret.
5. Copy the Webhook URL shown in the settings and add it to your Crynova project
   webhooks: `https://your-shop/?wc-api=crynova`.

== How it works ==

* `process_payment()` creates an invoice via `POST /api/v1/invoices` and redirects
  the buyer to `checkout_url`.
* The webhook endpoint verifies the `X-Crynova-Sig` HMAC-SHA256 signature and
  updates the order on `invoice.paid`, `invoice.underpaid`, `invoice.expired`.

== Changelog ==

= 1.0.0 =
* Initial release. Compatible with Crynova API v1.
