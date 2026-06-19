<?php

namespace Flute\Modules\Crynova\Gateway\Message;

use Omnipay\Common\Message\AbstractRequest;

/**
 * Creates a Crynova invoice via POST /api/v1/invoices.
 */
class PurchaseRequest extends AbstractRequest
{
    public function getApiKey()
    {
        return $this->getParameter('apiKey');
    }

    public function setApiKey($value)
    {
        return $this->setParameter('apiKey', $value);
    }

    public function getApiBase()
    {
        return $this->getParameter('apiBase') ?: 'https://crynova.io/api/v1';
    }

    public function setApiBase($value)
    {
        return $this->setParameter('apiBase', $value);
    }

    public function getData(): array
    {
        $this->validate('amount', 'currency');

        return [
            'currency'    => $this->getCurrency(),
            'amount'      => (float) $this->getAmount(),
            'order_id'    => (string) ($this->getTransactionId() ?: uniqid('flute-', true)),
            'description' => $this->getDescription() ?: ('Order ' . $this->getTransactionId()),
            'metadata'    => array_filter(['source' => 'flute-cms']),
        ];
    }

    public function sendData($data)
    {
        $base = rtrim((string) $this->getApiBase(), '/');

        $ch = curl_init($base . '/invoices');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->getApiKey(),
                'Content-Type: application/json',
                'Accept: application/json',
                'Idempotency-Key: flute-' . $data['order_id'],
            ],
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode((string) $raw, true);
        if (! is_array($body)) {
            $body = [];
        }

        return $this->response = new PurchaseResponse($this, $body, $status);
    }
}
