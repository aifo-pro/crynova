<?php

namespace Crynova\Pay\Payment;

use XF\Entity\PaymentProfile;
use XF\Entity\PurchaseRequest;
use XF\Mvc\Controller;
use XF\Payment\AbstractProvider;
use XF\Payment\CallbackState;
use XF\Purchasable\Purchase;

/**
 * Crynova crypto payment provider for XenForo 2.2+.
 */
class Crynova extends AbstractProvider
{
    public function getTitle()
    {
        return 'Crynova (Crypto payments)';
    }

    public function getApiEndpoint(PaymentProfile $paymentProfile)
    {
        return rtrim($paymentProfile->options['api_base'] ?: 'https://crynova.io/api/v1', '/');
    }

    public function verifyConfig(array &$options, &$errors = [])
    {
        if (empty($options['api_key']) || empty($options['webhook_secret'])) {
            $errors[] = 'Please enter the Crynova API key and webhook secret.';
            return false;
        }
        if (empty($options['api_base'])) {
            $options['api_base'] = 'https://crynova.io/api/v1';
        }
        return true;
    }

    /** Create the invoice and redirect the buyer to the Crynova checkout. */
    public function initiatePayment(Controller $controller, PurchaseRequest $purchaseRequest, Purchase $purchase)
    {
        $profile = $purchase->paymentProfile;

        $payload = json_encode([
            'currency'    => $purchase->currency,
            'amount'      => $purchase->cost,
            'order_id'    => $purchaseRequest->request_key,
            'description' => $purchase->title,
            'metadata'    => ['source' => 'xenforo'],
        ]);

        $ch = curl_init($this->getApiEndpoint($profile) . '/invoices');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $profile->options['api_key'],
                'Content-Type: application/json',
                'Accept: application/json',
                'Idempotency-Key: xf-' . $purchaseRequest->request_key,
            ],
        ]);
        $result = json_decode((string) curl_exec($ch), true);
        curl_close($ch);

        if (empty($result['checkout_url'])) {
            throw $controller->exception($controller->error('Crynova: could not create the invoice.'));
        }

        return $controller->redirect($result['checkout_url']);
    }

    /** Parse the incoming webhook into a CallbackState. */
    public function setupCallback(\XF\Http\Request $request)
    {
        $state = new CallbackState();

        $raw = $request->getInputRaw();
        $json = json_decode($raw, true) ?: [];
        $data = $json['data'] ?? $json;

        $state->inputRaw       = $raw;
        $state->event          = $json['event'] ?? '';
        $state->requestKey     = $data['order_id'] ?? null;
        $state->transactionId  = $data['invoice_id'] ?? ($data['order_id'] ?? '');
        $state->signature      = $request->getServer('HTTP_X_CRYNOVA_SIG') ?: '';
        $state->_POST          = $data;

        return $state;
    }

    public function validateCallback(CallbackState $state)
    {
        $purchaseRequest = $state->getPurchaseRequest();
        if (!$purchaseRequest) {
            $state->logType = 'error';
            $state->logMessage = 'Unknown purchase request.';
            return false;
        }

        $profile = $state->getPaymentProfile();
        $secret = $profile ? (string) $profile->options['webhook_secret'] : '';

        $sig = $state->signature;
        $provided = strpos($sig, 'sha256=') === 0 ? substr($sig, 7) : $sig;
        $expected = hash_hmac('sha256', $state->inputRaw, $secret);

        if (!$secret || !hash_equals($expected, $provided)) {
            $state->logType = 'error';
            $state->logMessage = 'Invalid webhook signature.';
            $state->httpCode = 400;
            return false;
        }

        return true;
    }

    public function validateCost(CallbackState $state)
    {
        return true; // amount is enforced by Crynova for the created invoice
    }

    public function getPaymentResult(CallbackState $state)
    {
        if ($state->event === 'invoice.paid') {
            $state->paymentResult = CallbackState::PAYMENT_RECEIVED;
        } elseif (in_array($state->event, ['invoice.expired'], true)) {
            $state->paymentResult = CallbackState::PAYMENT_REVERSED;
        }
    }

    public function prepareLogData(CallbackState $state)
    {
        $state->logDetails = [
            'event'   => $state->event,
            'payload' => $state->_POST,
        ];
    }

    public function supportsRecurring(PaymentProfile $paymentProfile, $unit, $amount, &$result = self::ERR_NO_RECURRING)
    {
        return false;
    }
}
