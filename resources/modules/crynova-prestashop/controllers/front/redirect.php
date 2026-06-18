<?php
/**
 * Creates a Crynova invoice for the current cart and redirects to checkout.
 */
class CrynovaPayRedirectModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;
        if (!$cart->id || !$this->module->active) {
            Tools::redirect('index.php?controller=order');
        }

        $customer = new Customer($cart->id_customer);
        $currency = new Currency($cart->id_currency);
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

        // Create the PrestaShop order first (status: awaiting payment).
        $this->module->validateOrder(
            $cart->id,
            (int) Configuration::get('PS_OS_PREPARATION'),
            $total,
            $this->module->displayName,
            null,
            [],
            (int) $cart->id_currency,
            false,
            $customer->secure_key
        );
        $orderId = $this->module->currentOrder;

        $base = rtrim(Configuration::get('CRYNOVA_API_BASE') ?: 'https://crynova.io/api/v1', '/');
        $payload = json_encode([
            'currency' => $currency->iso_code,
            'amount' => round($total, 2),
            'order_id' => (string) $orderId,
            'description' => Configuration::get('PS_SHOP_NAME').' — order #'.$orderId,
            'metadata' => ['source' => 'prestashop'],
        ]);

        $ch = curl_init($base.'/invoices');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.Configuration::get('CRYNOVA_API_KEY'),
                'Content-Type: application/json',
                'Accept: application/json',
                'Idempotency-Key: ps-'.$orderId,
            ],
        ]);
        $res = json_decode((string) curl_exec($ch), true);
        curl_close($ch);

        if (!empty($res['checkout_url'])) {
            Tools::redirect($res['checkout_url']);
        }

        Tools::redirect('index.php?controller=order&step=1');
    }
}
