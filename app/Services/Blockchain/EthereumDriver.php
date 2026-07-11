<?php

namespace App\Services\Blockchain;

use App\Models\Currency;
use App\Models\Setting;
use App\Services\BlockchainDriverInterface;
use App\Services\HdWalletService;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class EthereumDriver implements BlockchainDriverInterface
{
    private string $nodeUrl;
    private int $chainId;

    public function __construct(
        private readonly HdWalletService $hdWallet,
    ) {
        $this->nodeUrl = config('crynova.eth.node_url');
        $this->chainId = (int) config('crynova.eth.chain_id', 1);
    }

    public function deriveAddress(int $index): array
    {
        if (! $this->hdWallet->hasXpub('ethereum')) {
            throw new RuntimeException('ETH/BSC HD xpub is not configured. Set hd_xpub_eth in admin settings or HD_XPUB_ETH in .env.');
        }

        return $this->hdWallet->deriveEthereum($index);
    }

    public function getBalance(string $address): string
    {
        $result = $this->jsonRpc('eth_getBalance', [$address, 'latest']);
        $wei = gmp_strval(gmp_init(ltrim((string) $result, '0x') ?: '0', 16));

        return bcdiv($wei, bcpow('10', '18', 0), 18);
    }

    public function getTransactions(string $address, int $fromBlock = 0, ?Currency $currency = null): array
    {
        $explorer = $this->explorerConfig($currency);

        if (! $explorer['api_key']) {
            return [];
        }

        $action = ($currency?->contract_address) ? 'tokentx' : 'txlist';
        $params = [
            'module'     => 'account',
            'action'     => $action,
            'address'    => $address,
            'startblock' => max(0, $fromBlock),
            'endblock'   => 99999999,
            'sort'       => 'desc',
            'apikey'     => $explorer['api_key'],
        ];

        if ($currency?->contract_address) {
            $params['contractaddress'] = $currency->contract_address;
        }

        if (! empty($explorer['chainid'])) {
            $params['chainid'] = $explorer['chainid'];
        }

        $response = Http::timeout(15)->get($explorer['base_url'], $params)->json();
        $rows = $response['result'] ?? [];

        if (! is_array($rows) || isset($rows['message'])) {
            return [];
        }

        $decimals = $currency?->decimals ?? 18;

        return collect($rows)
            ->filter(fn ($tx) => strtolower($tx['to'] ?? '') === strtolower($address))
            ->map(function (array $tx) use ($decimals, $currency) {
                $block = (int) ($tx['blockNumber'] ?? 0);
                // Etherscan returns a per-tx confirmations count that is correct on
                // every chain (mainnet + L2s); using it avoids cross-chain block-height
                // mismatches (an ETH-mainnet height vs an L2 block number).
                $confirmations = (int) ($tx['confirmations'] ?? 0);
                $rawValue = $tx['value'] ?? '0';

                if ($currency?->contract_address) {
                    $rawValue = $tx['value'] ?? '0';
                    $amount = bcdiv((string) $rawValue, bcpow('10', (string) $decimals, 0), 18);
                } else {
                    $amount = bcdiv((string) $rawValue, bcpow('10', '18', 0), 18);
                }

                return [
                    'tx_hash'       => $tx['hash'],
                    'amount'        => $amount,
                    'confirmations' => $confirmations,
                    'from'          => $tx['from'] ?? null,
                    'blockindex'    => $block,
                    'blocktime'     => (int) ($tx['timeStamp'] ?? time()),
                ];
            })
            ->values()
            ->all();
    }

    public function broadcast(string $rawTx): string
    {
        return $this->jsonRpc('eth_sendRawTransaction', ['0x' . ltrim($rawTx, '0x')]);
    }

    public function getBlockHeight(): int
    {
        return (int) hexdec(ltrim((string) $this->jsonRpc('eth_blockNumber', []), '0x'));
    }

    /** EVM networks supported by this driver → Etherscan V2 chain id. */
    public const CHAIN_IDS = [
        'ethereum' => 1,
        'bsc'      => 56,
        'arbitrum' => 42161,
        'optimism' => 10,
        'base'     => 8453,
    ];

    private function explorerConfig(?Currency $currency): array
    {
        $network = $currency?->network ?? 'ethereum';
        $chainId = self::CHAIN_IDS[$network] ?? 1;

        // Etherscan V2 is a single endpoint that serves every chain via `chainid`
        // with one API key — used for Ethereum and all L2s (Arbitrum/Optimism/Base).
        $etherscanKey = Setting::get('etherscan_api_key') ?: config('crynova.eth.etherscan_api_key');
        if ($etherscanKey) {
            return [
                'base_url' => config('crynova.eth.explorer_v2_url', 'https://api.etherscan.io/v2/api'),
                'api_key'  => $etherscanKey,
                'chainid'  => $chainId,
            ];
        }

        // Legacy fallback: dedicated BscScan key for BSC only.
        if ($network === 'bsc') {
            return [
                'base_url' => config('crynova.bsc.explorer_url', 'https://api.bscscan.com/api'),
                'api_key'  => Setting::get('bscscan_api_key') ?: config('crynova.bsc.explorer_api_key'),
                'chainid'  => null,
            ];
        }

        return [
            'base_url' => config('crynova.eth.explorer_url', 'https://api.etherscan.io/api'),
            'api_key'  => null,
            'chainid'  => null,
        ];
    }

    private function jsonRpc(string $method, array $params = []): mixed
    {
        if (trim((string) $this->nodeUrl) === '') {
            throw new RuntimeException('EVM RPC URL не налаштовано (crynova.eth.node_url / ETH_NODE_URL).');
        }

        $response = Http::timeout(10)->post($this->nodeUrl, [
            'jsonrpc' => '2.0',
            'id'      => 1,
            'method'  => $method,
            'params'  => $params,
        ]);

        $body = $response->json();

        if (! is_array($body)) {
            throw new RuntimeException("EVM RPC [{$method}]: порожня або некоректна відповідь (HTTP {$response->status()}).");
        }

        if (isset($body['error'])) {
            throw new RuntimeException("EVM RPC error [{$method}]: " . ($body['error']['message'] ?? 'unknown'));
        }

        if (! array_key_exists('result', $body)) {
            throw new RuntimeException("EVM RPC [{$method}]: відсутнє поле result.");
        }

        return $body['result'];
    }
}
