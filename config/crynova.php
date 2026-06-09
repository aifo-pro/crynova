<?php

return [
    'invoice_ttl_minutes' => (int) env('INVOICE_TTL_MINUTES', 30),

    'webhook_timeout'      => (int) env('WEBHOOK_TIMEOUT_SECONDS', 10),
    'webhook_max_attempts' => (int) env('WEBHOOK_MAX_ATTEMPTS', 5),

    'btc' => [
        'node_url'       => env('BTC_NODE_URL', 'http://127.0.0.1:8332'),
        'node_user'      => env('BTC_NODE_USER', 'rpcuser'),
        'node_pass'      => env('BTC_NODE_PASS', 'rpcpassword'),
        'network'        => env('BTC_NETWORK', 'mainnet'),
        'confirmations'  => (int) env('BTC_CONFIRMATIONS', 3),
    ],

    'eth' => [
        'node_url'           => env('ETH_NODE_URL', 'https://mainnet.infura.io/v3/'),
        'chain_id'           => (int) env('ETH_CHAIN_ID', 1),
        'confirmations'      => (int) env('ETH_CONFIRMATIONS', 12),
        'etherscan_api_key'  => env('ETHERSCAN_API_KEY', ''),
        'explorer_url'       => env('ETH_EXPLORER_URL', 'https://api.etherscan.io/api'),
    ],

    'bsc' => [
        'explorer_api_key' => env('BSCSCAN_API_KEY', env('ETHERSCAN_API_KEY', '')),
        'explorer_url'     => env('BSC_EXPLORER_URL', 'https://api.bscscan.com/api'),
        'confirmations'    => (int) env('BSC_CONFIRMATIONS', 15),
    ],

    'tron' => [
        'node_url'       => env('TRON_NODE_URL', 'https://api.trongrid.io'),
        'api_key'        => env('TRON_API_KEY', ''),
        'confirmations'  => (int) env('TRX_CONFIRMATIONS', 20),
    ],

    'ltc' => [
        'node_url'       => env('LTC_NODE_URL', 'http://127.0.0.1:9332'),
        'node_user'      => env('LTC_NODE_USER', 'rpcuser'),
        'node_pass'      => env('LTC_NODE_PASS', 'rpcpassword'),
        'confirmations'  => (int) env('LTC_CONFIRMATIONS', 6),
    ],

    'doge' => [
        'node_url'       => env('DOGE_NODE_URL', 'http://127.0.0.1:22555'),
        'node_user'      => env('DOGE_NODE_USER', 'rpcuser'),
        'node_pass'      => env('DOGE_NODE_PASS', 'rpcpassword'),
        'confirmations'  => (int) env('DOGE_CONFIRMATIONS', 6),
    ],
];
