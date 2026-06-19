<?php

namespace Flute\Modules\Crynova\Gateway\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * Result of verifying a Crynova webhook. Successful only when the signature is
 * valid and the event confirms a completed payment.
 */
class CompletePurchaseResponse extends AbstractResponse
{
    public function isSuccessful(): bool
    {
        return ! empty($this->data['valid']) && $this->data['event'] === 'invoice.paid';
    }

    public function isCancelled(): bool
    {
        return ! empty($this->data['valid']) && $this->data['event'] === 'invoice.expired';
    }

    public function getTransactionReference(): ?string
    {
        return $this->data['transactionReference'] ?: null;
    }

    public function getTransactionId(): ?string
    {
        return $this->data['transactionId'] ?: null;
    }

    public function getAmount()
    {
        return $this->data['amount'];
    }

    public function getMessage(): ?string
    {
        return $this->isSuccessful() ? 'Payment confirmed' : 'Payment not confirmed or invalid signature';
    }
}
