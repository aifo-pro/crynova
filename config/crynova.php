<?php

return [
    'invoice_ttl_minutes' => (int) env('INVOICE_TTL_MINUTES', 30),

    'webhook_timeout'      => (int) env('WEBHOOK_TIMEOUT_SECONDS', 10),
    'webhook_max_attempts' => (int) env('WEBHOOK_MAX_ATTEMPTS', 5),

    // Shared explorer credentials for UTXO chains (raise rate limits / fallback).
    'blockcypher_token' => env('BLOCKCYPHER_TOKEN', ''),
    'blockchair_key'    => env('BLOCKCHAIR_KEY', ''),

    // Fiat currencies an invoice can be priced in. The customer then picks a
    // crypto at checkout and the fiat amount is converted to it via live rates.
    'fiat_currencies' => [
        'USD', 'EUR', 'GBP', 'JPY', 'CNY', 'RUB', 'INR', 'AUD', 'CAD', 'SGD',
        'HKD', 'TRY', 'AED', 'THB', 'MYR', 'PHP', 'IDR', 'VND', 'KZT', 'UAH',
        'BYN', 'UZS', 'KGS', 'AMD', 'AZN', 'PLN',
    ],
    'fiat_rates_url' => env('FIAT_RATES_URL', 'https://open.er-api.com/v6/latest/USD'),

    'btc' => [
        'node_url'       => env('BTC_NODE_URL', 'http://127.0.0.1:8332'),
        'node_user'      => env('BTC_NODE_USER', 'rpcuser'),
        'node_pass'      => env('BTC_NODE_PASS', 'rpcpassword'),
        'network'        => env('BTC_NETWORK', 'mainnet'),
        'confirmations'  => (int) env('BTC_CONFIRMATIONS', 3),
        'explorer_url'   => env('BTC_EXPLORER_URL', 'https://api.blockcypher.com/v1/btc/main'),
    ],

    'eth' => [
        'node_url'           => env('ETH_NODE_URL', 'https://mainnet.infura.io/v3/'),
        'chain_id'           => (int) env('ETH_CHAIN_ID', 1),
        'confirmations'      => (int) env('ETH_CONFIRMATIONS', 12),
        'etherscan_api_key'  => env('ETHERSCAN_API_KEY', ''),
        'explorer_url'       => env('ETH_EXPLORER_URL', 'https://api.etherscan.io/api'),
        // Etherscan V2: one key serves Ethereum + all L2s (Arbitrum/Optimism/Base) via chainid.
        'explorer_v2_url'    => env('ETHERSCAN_V2_URL', 'https://api.etherscan.io/v2/api'),
    ],

    'solana' => [
        'node_url'      => env('SOLANA_NODE_URL', 'https://api.mainnet-beta.solana.com'),
        'confirmations' => (int) env('SOLANA_CONFIRMATIONS', 1),
    ],

    'ton' => [
        'api_url'       => env('TON_API_URL', 'https://toncenter.com/api/v2'),
        'api_key'       => env('TON_API_KEY', ''),
        // tonapi.io returns parsed Jetton transfers (used for TON token deposits).
        'tonapi_url'    => env('TONAPI_URL', 'https://tonapi.io'),
        'tonapi_key'    => env('TONAPI_KEY', ''),
        'confirmations' => (int) env('TON_CONFIRMATIONS', 1),
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
        'explorer_url'   => env('LTC_EXPLORER_URL', 'https://api.blockcypher.com/v1/ltc/main'),
    ],

    'doge' => [
        'node_url'       => env('DOGE_NODE_URL', 'http://127.0.0.1:22555'),
        'node_user'      => env('DOGE_NODE_USER', 'rpcuser'),
        'node_pass'      => env('DOGE_NODE_PASS', 'rpcpassword'),
        'confirmations'  => (int) env('DOGE_CONFIRMATIONS', 6),
        'explorer_url'   => env('DOGE_EXPLORER_URL', 'https://api.blockcypher.com/v1/doge/main'),
    ],
];
