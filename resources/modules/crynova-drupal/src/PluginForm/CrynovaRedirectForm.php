<?php

namespace Drupal\crynova_commerce\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the off-site redirect to the Crynova hosted checkout.
 */
class CrynovaRedirectForm extends BasePaymentOffsiteForm
{
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        $payment = $this->entity;
        /** @var \Drupal\crynova_commerce\Plugin\Commerce\PaymentGateway\Crynova $gateway */
        $gateway = $payment->getPaymentGateway()->getPlugin();

        $invoice = $gateway->createInvoice($payment->getOrder());

        if (empty($invoice['checkout_url'])) {
            throw new \Exception('Crynova: could not create invoice.');
        }

        return $this->buildRedirectForm($form, $form_state, $invoice['checkout_url'], [], self::REDIRECT_GET);
    }
}
