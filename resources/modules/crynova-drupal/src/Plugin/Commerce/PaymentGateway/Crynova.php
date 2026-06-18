<?php

namespace Drupal\crynova_commerce\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Crynova off-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "crynova",
 *   label = "Crynova (Crypto payments)",
 *   display_label = "Cryptocurrency (Crynova)",
 *   forms = {
 *     "offsite-payment" = "Drupal\crynova_commerce\PluginForm\CrynovaRedirectForm",
 *   },
 * )
 */
class Crynova extends OffsitePaymentGatewayBase
{
    public function defaultConfiguration()
    {
        return [
            'api_key'        => '',
            'webhook_secret' => '',
            'api_base'       => 'https://crynova.io/api/v1',
        ] + parent::defaultConfiguration();
    }

    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $form['api_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('API Key'),
            '#default_value' => $this->configuration['api_key'],
            '#required' => TRUE,
        ];
        $form['webhook_secret'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Webhook Secret'),
            '#default_value' => $this->configuration['webhook_secret'],
            '#required' => TRUE,
        ];
        $form['api_base'] = [
            '#type' => 'textfield',
            '#title' => $this->t('API Base URL'),
            '#default_value' => $this->configuration['api_base'],
        ];
        $form['webhook_help'] = [
            '#markup' => $this->t('Webhook URL: <code>@url</code>', ['@url' => \Drupal::request()->getSchemeAndHttpHost() . '/crynova/webhook']),
        ];

        return $form;
    }

    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitConfigurationForm($form, $form_state);
        $values = $form_state->getValue($form['#parents']);
        $this->configuration['api_key'] = trim($values['api_key']);
        $this->configuration['webhook_secret'] = trim($values['webhook_secret']);
        $this->configuration['api_base'] = rtrim(trim($values['api_base']), '/') ?: 'https://crynova.io/api/v1';
    }

    /** Create a Crynova invoice for an order; returns the decoded response. */
    public function createInvoice($order): array
    {
        $amount = $order->getTotalPrice();
        $payload = json_encode([
            'currency'    => $amount->getCurrencyCode(),
            'amount'      => (float) $amount->getNumber(),
            'order_id'    => (string) $order->id(),
            'description' => 'Order #' . $order->id(),
            'metadata'    => ['source' => 'drupal'],
        ]);

        $ch = curl_init(rtrim($this->configuration['api_base'], '/') . '/invoices');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POST => TRUE,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->configuration['api_key'],
                'Content-Type: application/json',
                'Accept: application/json',
                'Idempotency-Key: drupal-' . $order->id(),
            ],
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode((string) $raw, TRUE);

        return is_array($decoded) ? $decoded : [];
    }

    /** Customer returned from the Crynova checkout — create a pending payment. */
    public function onReturn(OrderInterface $order, Request $request)
    {
        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        $payment = $payment_storage->create([
            'state' => 'pending',
            'amount' => $order->getTotalPrice(),
            'payment_gateway' => $this->parentEntity->id(),
            'order_id' => $order->id(),
            'remote_id' => $request->query->get('invoice_id'),
        ]);
        $payment->save();
    }
}
