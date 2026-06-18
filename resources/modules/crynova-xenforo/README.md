# Crynova for XenForo 2.2+

Accept crypto payments (BTC, ETH, USDT, TRX, LTC, DOGE) for XenForo account
upgrades and purchases via Crynova.

## Installation
1. Upload the contents of `upload/` to your XenForo root (so the add-on lands in
   `src/addons/Crynova/Pay/`).
2. Admin CP → Add-ons → install **Crynova Crypto Payments**.
3. Admin CP → Setup → Payments → **Payment profiles** → add a profile using the
   **Crynova (Crypto payments)** provider.
4. Enter your **API Key**, **Webhook Secret** and **API Base URL**.
5. Add your forum's payment callback URL
   (`https://your-forum/payment_callback.php?_xfProvider=crynova`) to your
   Crynova project webhooks.

## Flow
- `initiatePayment()` creates an invoice via `POST /api/v1/invoices` and redirects
  the buyer to the Crynova checkout.
- The payment callback verifies the `X-Crynova-Sig` signature and, on
  `invoice.paid`, completes the XenForo purchase.

Compatible with XenForo 2.2+ and Crynova API v1.
