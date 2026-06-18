# Crynova × Tilda

Accept crypto payments on Tilda by routing checkout through Crynova.

Tilda has no server-side plugin API, so this is a tiny self-hosted PHP bridge.

## Setup
1. Upload this folder to your PHP hosting (e.g. `https://pay.your-site.com/`).
2. Edit `config.php` — set `api_key`, `webhook_secret`, success/fail URLs.
3. In Tilda, point your payment button / form action to
   `https://pay.your-site.com/pay.php` and pass `amount` and `orderid`
   (and optionally `currency`, `email`).
4. Add `https://pay.your-site.com/webhook.php` to your Crynova project webhooks.

## Flow
- `pay.php` creates a Crynova invoice (`POST /api/v1/invoices`) and redirects the
  customer to `checkout_url`.
- `webhook.php` verifies the `X-Crynova-Sig` signature; on `invoice.paid` you can
  grant access / send a confirmation (see the TODO in the file).

Compatible with Crynova API v1.
