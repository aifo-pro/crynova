# Crynova × GetCourse

Accept crypto payments for GetCourse deals via Crynova (self-hosted PHP bridge).

## Setup
1. Upload this folder to your PHP hosting.
2. Edit `config.php` — Crynova `api_key`/`webhook_secret`, your GetCourse
   `gc_account` and `gc_secret` (Settings → API), and the paid deal status.
3. In GetCourse, create a custom payment system and set its **Payment URL** to
   `https://your-host/pay.php`.
4. Add `https://your-host/webhook.php` to your Crynova project webhooks.

## Flow
- `pay.php` creates a Crynova invoice for the GetCourse deal and redirects the
  buyer to `checkout_url`.
- `webhook.php` verifies the signature and, on `invoice.paid`, marks the deal as
  paid through the GetCourse deals API.

Compatible with Crynova API v1.
