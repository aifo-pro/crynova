# Crynova Commerce (Drupal)

Crypto payments for Drupal Commerce 2.x via the Crynova gateway.

## Requirements
- Drupal 9 or 10
- Drupal Commerce (`commerce_payment`)

## Installation
1. Place the `crynova_commerce` folder in `modules/custom/`.
2. Enable it: Extend → **Crynova Commerce** (or `drush en crynova_commerce`).
3. Commerce → Configuration → Payment gateways → **Add payment gateway** →
   choose **Crynova**.
4. Enter your **API Key**, **Webhook Secret** and **API Base URL**.
5. Copy the Webhook URL (`/crynova/webhook`) into your Crynova project webhooks.

## Flow
- At checkout the customer is redirected off-site to the Crynova hosted page
  (an invoice is created via `POST /api/v1/invoices`).
- `/crynova/webhook` verifies the `X-Crynova-Sig` signature and completes the
  Commerce payment on `invoice.paid`.

Compatible with Crynova API v1.
