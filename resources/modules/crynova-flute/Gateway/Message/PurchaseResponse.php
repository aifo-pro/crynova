<?php

namespace Flute\Modules\Crynova\Gateway\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;

/**
 * Redirect response — sends the customer to the Crynova hosted checkout.
 */
class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{
    protected $statusCode;

    public function __construct(RequestInterface $request, $data, int $statusCode = 200)
    {
        parent::__construct($request, $data);
        $this->statusCode = $statusCode;
    }

    public function isSuccessful(): bool
    {
        return false; // payment completes off-site, then via webhook
    }

    public function isRedirect(): bool
    {
        return $this->statusCode < 300 && ! empty($this->data['checkout_url']);
    }

    public function getRedirectUrl(): ?string
    {
        return $this->data['checkout_url'] ?? null;
    }

    public function getRedirectMethod(): string
    {
        return 'GET';
    }

    public function getRedirectData(): array
    {
        return [];
    }

    public function getTransactionReference(): ?string
    {
        return $this->data['invoice_id'] ?? null;
    }

    public function getMessage(): ?string
    {
        return $this->data['error'] ?? null;
    }
}
