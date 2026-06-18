<?php
/**
 * Verified Crynova webhook receiver for PrestaShop.
 */
class CrynovaPayWebhookModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $secret = (string) Configuration::get('CRYNOVA_WEBHOOK_SECRET');
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_CRYNOVA_SIG'] ?? '';
        $provided = strpos($signature, 'sha256=') === 0 ? substr($signature, 7) : $signature;
        $expected = hash_hmac('sha256', $payload, $secret);

        if (!$secret || !hash_equals($expected, $provided)) {
            http_response_code(400);
            exit('Invalid signature');
        }

        $event = json_decode($payload, true);
        $data = $event['data'] ?? $event;
        $orderId = isset($data['order_id']) ? (int) $data['order_id'] : 0;
        $order = $orderId ? new Order($orderId) : null;

        if (!$order || !Validate::isLoadedObject($order)) {
            http_response_code(404);
            exit('Order not found');
        }

        if (($event['event'] ?? '') === 'invoice.paid') {
            if ((int) $order->getCurrentState() !== (int) Configuration::get('PS_OS_PAYMENT')) {
                $order->setCurrentState((int) Configuration::get('PS_OS_PAYMENT'));
            }
        } elseif (($event['event'] ?? '') === 'invoice.expired') {
            $order->setCurrentState((int) Configuration::get('PS_OS_CANCELED'));
        }

        http_response_code(200);
        exit('OK');
    }
}
