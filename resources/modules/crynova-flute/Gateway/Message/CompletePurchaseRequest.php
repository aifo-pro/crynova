<?php

namespace Flute\Modules\Crynova\Gateway\Message;

use Omnipay\Common\Message\AbstractRequest;

/**
 * Verifies a signed Crynova webhook and resolves the payment status.
 */
class CompletePurchaseRequest extends AbstractRequest
{
    public function getWebhookSecret()
    {
        return $this->getParameter('webhookSecret');
    }

    public function setWebhookSecret($value)
    {
        return $this->setParameter('webhookSecret', $value);
    }

    public function getData(): array
    {
        $payload = (string) ($this->httpRequest->getContent() ?: file_get_contents('php://input'));
        $signature = $this->httpRequest->headers->get('X-Crynova-Sig', '');

        return [
            'payload'   => $payload,
            'signature' => $signature,
            'event'     => json_decode($payload, true) ?: [],
        ];
    }

    public function sendData($data)
    {
        $secret = (string) $this->getWebhookSecret();
        $provided = strpos((string) $data['signature'], 'sha256=') === 0
            ? substr($data['signature'], 7)
            : (string) $data['signature'];
        $expected = hash_hmac('sha256', $data['payload'], $secret);

        $valid = $secret !== '' && hash_equals($expected, $provided);
        $event = $data['event'];
        $body  = $event['data'] ?? $event;

        return $this->response = new CompletePurchaseResponse($this, [
            'valid'                 => $valid,
            'event'                 => $event['event'] ?? '',
            'transactionReference'  => $body['invoice_id'] ?? '',
            'transactionId'         => $body['order_id'] ?? '',
            'amount'                => $body['amount'] ?? null,
            'currency'              => $body['currency'] ?? null,
        ]);
    }
}
