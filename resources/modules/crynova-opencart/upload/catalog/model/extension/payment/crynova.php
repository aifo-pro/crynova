<?php
/**
 * Crynova payment extension for OpenCart 3.x — catalog model.
 * Exposes the payment method to the checkout when enabled.
 */
class ModelExtensionPaymentCrynova extends Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/crynova');

        if (!$this->config->get('payment_crynova_status')) {
            return [];
        }

        return [
            'code'       => 'crynova',
            'title'      => $this->language->get('text_title'),
            'terms'      => '',
            'sort_order' => $this->config->get('payment_crynova_sort_order'),
        ];
    }
}
