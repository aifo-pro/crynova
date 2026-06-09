<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['group' => 'fees',    'key' => 'default_fee_percent',      'value' => '1.00',  'type' => 'string', 'description' => 'Default merchant fee %'],
            ['group' => 'fees',    'key' => 'min_withdrawal_usd',        'value' => '10',    'type' => 'int',    'description' => 'Minimum withdrawal in USD equivalent'],
            ['group' => 'invoices','key' => 'invoice_ttl_minutes',       'value' => '30',    'type' => 'int',    'description' => 'Invoice expiry TTL in minutes'],
            ['group' => 'invoices','key' => 'require_exact_amount',      'value' => 'false', 'type' => 'bool',   'description' => 'Reject payments that are not exact amount'],
            ['group' => 'risk',    'key' => 'require_2fa_for_withdrawals','value' => 'true', 'type' => 'bool',   'description' => 'Require 2FA for withdrawal requests'],
            ['group' => 'risk',    'key' => 'manual_withdrawal_review',  'value' => 'true',  'type' => 'bool',   'description' => 'Admin must approve all withdrawals'],
            ['group' => 'risk',    'key' => 'max_invoice_amount_usd',    'value' => '50000', 'type' => 'int',    'description' => 'Max single invoice in USD (0 = no limit)'],
            ['group' => 'webhooks','key' => 'webhook_timeout_seconds',   'value' => '10',    'type' => 'int',    'description' => 'Webhook HTTP timeout in seconds'],
            ['group' => 'webhooks','key' => 'webhook_max_attempts',      'value' => '5',     'type' => 'int',    'description' => 'Max retry attempts for failed webhooks'],
        ];

        foreach ($settings as $data) {
            Setting::updateOrCreate(['key' => $data['key']], $data);
        }
    }
}
