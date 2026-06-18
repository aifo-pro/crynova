<?php
/**
 * Plugin Name: Crynova for WooCommerce
 * Plugin URI:  https://crynova.io
 * Description: Accept cryptocurrency payments (BTC, ETH, USDT, TRX, LTC, DOGE…) in WooCommerce via the Crynova payment gateway.
 * Version:     1.0.0
 * Author:      Crynova
 * Author URI:  https://crynova.io
 * License:     MIT
 * Text Domain: crynova
 * WC requires at least: 5.0
 * Requires PHP: 7.4
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'crynova_init_gateway', 11);

function crynova_init_gateway()
{
    if (! class_exists('WC_Payment_Gateway')) {
        return;
    }

    class WC_Gateway_Crynova extends WC_Payment_Gateway
    {
        public string $api_key;
        public string $webhook_secret;
        public string $api_base;

        public function __construct()
        {
            $this->id                 = 'crynova';
            $this->method_title       = 'Crynova';
            $this->method_description = 'Accept crypto payments via Crynova.';
            $this->has_fields         = false;
            $this->icon               = '';

            $this->init_form_fields();
            $this->init_settings();

            $this->title          = $this->get_option('title', 'Cryptocurrency (Crynova)');
            $this->description    = $this->get_option('description', 'Pay with Bitcoin, USDT, Ethereum and more.');
            $this->enabled        = $this->get_option('enabled', 'no');
            $this->api_key        = trim((string) $this->get_option('api_key'));
            $this->webhook_secret = trim((string) $this->get_option('webhook_secret'));
            $this->api_base       = rtrim($this->get_option('api_base', 'https://crynova.io/api/v1'), '/');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
            // Webhook endpoint: https://your-shop/?wc-api=crynova
            add_action('woocommerce_api_crynova', [$this, 'handle_webhook']);
        }

        public function init_form_fields()
        {
            $this->form_fields = [
                'enabled' => [
                    'title'   => 'Enable/Disable',
                    'type'    => 'checkbox',
                    'label'   => 'Enable Crynova crypto payments',
                    'default' => 'no',
                ],
                'title' => [
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'Payment method title shown at checkout.',
                    'default'     => 'Cryptocurrency (Crynova)',
                    'desc_tip'    => true,
                ],
                'description' => [
                    'title'   => 'Description',
                    'type'    => 'textarea',
                    'default' => 'Pay with Bitcoin, USDT, Ethereum and more.',
                ],
                'api_key' => [
                    'title'       => 'API Key',
                    'type'        => 'password',
                    'description' => 'Project → Integration → API keys in your Crynova dashboard.',
                    'default'     => '',
                ],
                'webhook_secret' => [
                    'title'       => 'Webhook Secret',
                    'type'        => 'password',
                    'description' => 'Project webhook secret — used to verify incoming payment notifications.',
                    'default'     => '',
                ],
                'api_base' => [
                    'title'       => 'API Base URL',
                    'type'        => 'text',
                    'default'     => 'https://crynova.io/api/v1',
                    'description' => 'Change only for a self-hosted Crynova instance.',
                ],
                'webhook_hint' => [
                    'title'       => 'Webhook URL',
                    'type'        => 'title',
                    'description' => 'Add this URL in your Crynova project webhooks: <code>' . esc_html(home_url('/?wc-api=crynova')) . '</code>',
                ],
            ];
        }

        /** Create a Crynova invoice and redirect the customer to the hosted checkout. */
        public function process_payment($order_id)
        {
            $order = wc_get_order($order_id);

            $payload = [
                'currency'    => $order->get_currency(),               // fiat — customer picks crypto at checkout
                'amount'      => (float) $order->get_total(),
                'order_id'    => (string) $order->get_id(),
                'description' => get_bloginfo('name') . ' — order #' . $order->get_id(),
                'metadata'    => [
                    'order_key' => $order->get_order_key(),
                    'source'    => 'woocommerce',
                ],
            ];

            $response = wp_remote_post($this->api_base . '/invoices', [
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'Idempotency-Key' => 'wc-' . $order->get_id(),
                ],
                'body' => wp_json_encode($payload),
            ]);

            if (is_wp_error($response)) {
                wc_add_notice('Payment error: ' . $response->get_error_message(), 'error');
                return ['result' => 'failure'];
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if ($code < 200 || $code >= 300 || empty($body['checkout_url'])) {
                $msg = $body['error'] ?? ('HTTP ' . $code);
                wc_add_notice('Payment error: ' . $msg, 'error');
                return ['result' => 'failure'];
            }

            $order->update_status('pending', 'Awaiting Crynova crypto payment.');
            if (! empty($body['invoice_id'])) {
                $order->update_meta_data('_crynova_invoice_id', $body['invoice_id']);
                $order->save();
            }

            return [
                'result'   => 'success',
                'redirect' => $body['checkout_url'],
            ];
        }

        /** Verify the signed webhook and update the order. */
        public function handle_webhook()
        {
            $payload   = file_get_contents('php://input');
            $signature = $_SERVER['HTTP_X_CRYNOVA_SIG'] ?? '';

            $provided = strpos($signature, 'sha256=') === 0 ? substr($signature, 7) : $signature;
            $expected = hash_hmac('sha256', $payload, $this->webhook_secret);

            if (! $this->webhook_secret || ! hash_equals($expected, $provided)) {
                status_header(400);
                exit('Invalid signature');
            }

            $event = json_decode($payload, true);
            $data  = $event['data'] ?? $event;
            $orderId = $data['order_id'] ?? null;
            $order   = $orderId ? wc_get_order((int) $orderId) : null;

            if (! $order) {
                status_header(404);
                exit('Order not found');
            }

            switch ($event['event'] ?? '') {
                case 'invoice.paid':
                    if (! $order->is_paid()) {
                        $order->payment_complete($data['invoice_id'] ?? '');
                        $order->add_order_note('Crynova: payment confirmed.');
                    }
                    break;
                case 'invoice.underpaid':
                    $order->update_status('on-hold', 'Crynova: underpaid, manual review needed.');
                    break;
                case 'invoice.expired':
                    if (! $order->is_paid()) {
                        $order->update_status('cancelled', 'Crynova: invoice expired.');
                    }
                    break;
            }

            status_header(200);
            exit('OK');
        }
    }

    add_filter('woocommerce_payment_gateways', function ($gateways) {
        $gateways[] = 'WC_Gateway_Crynova';
        return $gateways;
    });
}
