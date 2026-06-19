<?php

namespace Flute\Modules\Crynova\Gateway;

use Omnipay\Common\AbstractGateway;
use Flute\Modules\Crynova\Gateway\Message\PurchaseRequest;
use Flute\Modules\Crynova\Gateway\Message\CompletePurchaseRequest;

/**
 * Omnipay-compatible gateway for the Crynova crypto payment API.
 */
class CrynovaGateway extends AbstractGateway
{
    public function getName(): string
    {
        return 'Crynova';
    }

    public function getDefaultParameters(): array
    {
        return [
            'apiKey'        => '',
            'webhookSecret' => '',
            'apiBase'       => 'https://crynova.io/api/v1',
            'testMode'      => false,
        ];
    }

    public function getApiKey(): ?string
    {
        return $this->getParameter('apiKey');
    }

    public function setApiKey($value): self
    {
        return $this->setParameter('apiKey', $value);
    }

    public function getWebhookSecret(): ?string
    {
        return $this->getParameter('webhookSecret');
    }

    public function setWebhookSecret($value): self
    {
        return $this->setParameter('webhookSecret', $value);
    }

    public function getApiBase(): ?string
    {
        return $this->getParameter('apiBase') ?: 'https://crynova.io/api/v1';
    }

    public function setApiBase($value): self
    {
        return $this->setParameter('apiBase', $value);
    }

    /** Create an invoice and redirect the customer to the hosted checkout. */
    public function purchase(array $options = [])
    {
        return $this->createRequest(PurchaseRequest::class, $options);
    }

    /** Verify a signed webhook / confirm the payment status. */
    public function completePurchase(array $options = [])
    {
        return $this->createRequest(CompletePurchaseRequest::class, $options);
    }
}
