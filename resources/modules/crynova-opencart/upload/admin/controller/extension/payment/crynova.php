<?php
/**
 * Crynova payment extension for OpenCart 3.x — admin controller.
 */
class ControllerExtensionPaymentCrynova extends Controller
{
    private $error = [];

    public function index()
    {
        $this->load->language('extension/payment/crynova');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_crynova', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        foreach (['heading_title', 'text_edit', 'text_enabled', 'text_disabled', 'entry_status', 'entry_api_key', 'entry_webhook_secret', 'entry_api_base', 'entry_order_status', 'entry_webhook_url', 'button_save', 'button_cancel', 'help_webhook'] as $key) {
            $data[$key] = $this->language->get($key);
        }

        $data['error_warning'] = $this->error['warning'] ?? '';

        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/crynova', 'user_token=' . $this->session->data['user_token'], true),
        ];

        $data['action'] = $this->url->link('extension/payment/crynova', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);
        $data['webhook_url'] = HTTP_CATALOG . 'index.php?route=extension/payment/crynova/callback';

        // Fields (post value falls back to stored config).
        foreach (['payment_crynova_status', 'payment_crynova_api_key', 'payment_crynova_webhook_secret', 'payment_crynova_api_base', 'payment_crynova_order_status_id'] as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } else {
                $data[$field] = $this->config->get($field);
            }
        }
        if (empty($data['payment_crynova_api_base'])) {
            $data['payment_crynova_api_base'] = 'https://crynova.io/api/v1';
        }

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/crynova', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/crynova')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }

    public function install()
    {
        // Nothing extra to install — settings live in the setting table.
    }

    public function uninstall()
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('payment_crynova');
    }
}
