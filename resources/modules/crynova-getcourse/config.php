<?php
/**
 * Crynova × GetCourse — configuration.
 */
return [
    'api_key'        => 'sk_live_your_api_key',     // Crynova API key
    'webhook_secret' => 'your_webhook_secret',       // Crynova project webhook secret
    'api_base'       => 'https://crynova.io/api/v1',

    'gc_account'     => 'youraccount',               // youraccount.getcourse.ru
    'gc_secret'      => 'GETCOURSE_API_SECRET',       // GetCourse: Settings → API
    'gc_paid_status' => 'Оплачен',                    // deal status to set after payment

    'success_url'    => 'https://youraccount.getcourse.ru/thanks',
    'fail_url'       => 'https://youraccount.getcourse.ru/',
];
