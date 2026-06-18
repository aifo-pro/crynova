<?php

namespace Drupal\crynova_commerce\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Receives signed Crynova webhooks and updates Commerce payments.
 */
class WebhookController extends ControllerBase
{
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();

        // Find the gateway config to read the webhook secret.
        $gateways = $this->entityTypeManager()->getStorage('commerce_payment_gateway')->loadByProperties(['plugin' => 'crynova']);
        $gateway = reset($gateways);
        if (!$gateway) {
            return new Response('Gateway not configured', 404);
        }
        $secret = $gateway->getPlugin()->getConfiguration()['webhook_secret'] ?? '';

        $sig = $request->headers->get('X-Crynova-Sig', '');
        $provided = strpos($sig, 'sha256=') === 0 ? substr($sig, 7) : $sig;
        $expected = hash_hmac('sha256', $payload, (string) $secret);
        if (!$secret || !hash_equals($expected, $provided)) {
            return new Response('Invalid signature', 400);
        }

        $event = json_decode($payload, TRUE);
        $data = $event['data'] ?? $event;
        $orderId = $data['order_id'] ?? NULL;
        if (!$orderId) {
            return new Response('Order not found', 404);
        }

        $payments = $this->entityTypeManager()->getStorage('commerce_payment')->loadByProperties(['order_id' => $orderId]);
        $payment = reset($payments);

        if ($payment && ($event['event'] ?? '') === 'invoice.paid') {
            $payment->setState('completed');
            if (!empty($data['invoice_id'])) {
                $payment->setRemoteId($data['invoice_id']);
            }
            $payment->save();
        }

        return new Response('OK', 200);
    }
}
