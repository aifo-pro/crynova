<?php
/**
 * Crynova crypto payments for PrestaShop 1.7 / 8.x.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class CrynovaPay extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'crynovapay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Crynova';
        $this->controllers = ['redirect', 'webhook'];
        $this->currencies = true;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Crynova (Crypto payments)');
        $this->description = $this->l('Accept BTC, ETH, USDT, TRX, LTC, DOGE via Crynova.');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('paymentReturn');
    }

    public function uninstall()
    {
        foreach (['CRYNOVA_API_KEY', 'CRYNOVA_WEBHOOK_SECRET', 'CRYNOVA_API_BASE'] as $k) {
            Configuration::deleteByName($k);
        }
        return parent::uninstall();
    }

    /** Admin settings form. */
    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit_crynova')) {
            Configuration::updateValue('CRYNOVA_API_KEY', trim(Tools::getValue('CRYNOVA_API_KEY')));
            Configuration::updateValue('CRYNOVA_WEBHOOK_SECRET', trim(Tools::getValue('CRYNOVA_WEBHOOK_SECRET')));
            Configuration::updateValue('CRYNOVA_API_BASE', rtrim(trim(Tools::getValue('CRYNOVA_API_BASE')), '/'));
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        $webhook = $this->context->link->getModuleLink($this->name, 'webhook', [], true);
        $output .= '<div class="alert alert-info">'.$this->l('Webhook URL: ').'<code>'.$webhook.'</code></div>';

        return $output.$this->renderForm();
    }

    protected function renderForm()
    {
        $fields = [
            'form' => [
                'legend' => ['title' => $this->l('Crynova settings')],
                'input' => [
                    ['type' => 'text', 'label' => $this->l('API Key'), 'name' => 'CRYNOVA_API_KEY'],
                    ['type' => 'text', 'label' => $this->l('Webhook Secret'), 'name' => 'CRYNOVA_WEBHOOK_SECRET'],
                    ['type' => 'text', 'label' => $this->l('API Base URL'), 'name' => 'CRYNOVA_API_BASE'],
                ],
                'submit' => ['title' => $this->l('Save'), 'name' => 'submit_crynova'],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->fields_value = [
            'CRYNOVA_API_KEY' => Configuration::get('CRYNOVA_API_KEY'),
            'CRYNOVA_WEBHOOK_SECRET' => Configuration::get('CRYNOVA_WEBHOOK_SECRET'),
            'CRYNOVA_API_BASE' => Configuration::get('CRYNOVA_API_BASE') ?: 'https://crynova.io/api/v1',
        ];

        return $helper->generateForm([$fields]);
    }

    /** Show the payment option on checkout. */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return [];
        }

        $option = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $option->setCallToActionText($this->l('Pay with cryptocurrency (Crynova)'))
            ->setAction($this->context->link->getModuleLink($this->name, 'redirect', [], true));

        return [$option];
    }

    public function hookPaymentReturn($params)
    {
        return $this->active ? $this->l('Your crypto payment is being processed by Crynova.') : '';
    }
}
