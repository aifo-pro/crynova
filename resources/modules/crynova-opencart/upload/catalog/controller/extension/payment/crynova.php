<?php
/**
 * Crynova payment extension for OpenCart 3.x — catalog (storefront) controller.
 */
class ControllerExtensionPaymentCrynova extends Controller
{
    /** Renders the "Confirm order" button on the payment step. */
    public function index()
    {
        $this->load->language('extension/payment/crynova');
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['action'] = $this->url->link('extension/payment/crynova/confirm', '', true);

        return $this->load->view('extension/payment/crynova', $data);
    }

    /** Create a Crynova invoice for the current order and return the checkout URL. */
    public function confirm()
    {
        $this->load->language('extension/payment/crynova');
        $this->load->model('checkout/order');

        $json = [];

        if (!isset($this->session->data['order_id'])) {
            $json['error'] = $this->language->get('error_order');
            $this->respond($json);
            return;
        }

        $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        if (!$order) {
            $json['error'] = $this->language->get('error_order');
            $this->respond($json);
            return;
        }

        $apiBase = rtrim($this->config->get('payment_crynova_api_base') ?: 'https://crynova.io/api/v1', '/');
        $apiKey  = trim((string) $this->config->get('payment_crynova_api_key'));

        $payload = [
            'currency'    => $order['currency_code'],
            'amount'      => round((float) $order['total'] * (float) $order['currency_value'], 2),
            'order_id'    => (string) $order['order_id'],
            'description' => html_entity_decode($order['store_name']) . ' — order #' . $order['order_id'],
            'metadata'    => ['source' => 'opencart'],
        ];

        $result = $this->apiPost($apiBase . '/invoices', $apiKey, $payload, 'oc-' . $order['order_id']);

        if (empty($result['checkout_url'])) {
            $json['error'] = $result['error'] ?? $this->language->get('error_api');
            $this->respond($json);
            return;
        }

        // Mark the order pending so it appears in the admin; webhook completes it.
        $this->model_checkout_order->addOrderHistory($order['order_id'], 1, $this->language->get('text_pending'), false);

        $json['redirect'] = $result['checkout_url'];
        $this->respond($json);
    }

    /** Signed webhook receiver: index.php?route=extension/payment/crynova/callback */
    public function callback()
    {
        $secret    = trim((string) $this->config->get('payment_crynova_webhook_secret'));
        $payload   = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_CRYNOVA_SIG'] ?? '';
        $provided  = strpos($signature, 'sha256=') === 0 ? substr($signature, 7) : $signature;
        $expected  = hash_hmac('sha256', $payload, $secret);

        if (!$secret || !hash_equals($expected, $provided)) {
            http_response_code(400);
            echo 'Invalid signature';
            return;
        }

        $event = json_decode($payload, true);
        $data  = $event['data'] ?? $event;
        $orderId = isset($data['order_id']) ? (int) $data['order_id'] : 0;

        if (!$orderId) {
            http_response_code(404);
            echo 'Order not found';
            return;
        }

        $this->load->model('checkout/order');
        $this->load->language('extension/payment/crynova');

        switch ($event['event'] ?? '') {
            case 'invoice.paid':
                $statusId = (int) ($this->config->get('payment_crynova_order_status_id') ?: 2); // 2 = Processing
                $this->model_checkout_order->addOrderHistory($orderId, $statusId, $this->language->get('text_paid'), true);
                break;
            case 'invoice.expired':
                $this->model_checkout_order->addOrderHistory($orderId, 14, $this->language->get('text_expired'), false); // 14 = Expired
                break;
        }

        http_response_code(200);
        echo 'OK';
    }

    private function apiPost($url, $apiKey, array $payload, $idempotencyKey)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
                'Idempotency-Key: ' . $idempotencyKey,
            ],
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function respond(array $json)
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
