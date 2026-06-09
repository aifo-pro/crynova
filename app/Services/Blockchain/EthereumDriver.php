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

        $response = Http::timeout(15)->get($explorer['base_url'], $params)->json();
        $rows = $response['result'] ?? [];

        if (! is_array($rows) || isset($rows['message'])) {
            return [];
        }

        $currentBlock = $this->getBlockHeight();
        $decimals = $currency?->decimals ?? 18;

        return collect($rows)
            ->filter(fn ($tx) => strtolower($tx['to'] ?? '') === strtolower($address))
            ->map(function (array $tx) use ($currentBlock, $decimals, $currency) {
                $block = (int) ($tx['blockNumber'] ?? 0);
                $confirmations = $block > 0 ? max(0, $currentBlock - $block + 1) : 0;
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

    private function explorerConfig(?Currency $currency): array
    {
        $network = $currency?->network ?? 'ethereum';

        if ($network === 'bsc') {
            return [
                'base_url' => config('crynova.bsc.explorer_url', 'https://api.bscscan.com/api'),
                'api_key'  => Setting::get('bscscan_api_key') ?: config('crynova.bsc.explorer_api_key'),
            ];
        }

        return [
            'base_url' => config('crynova.eth.explorer_url', 'https://api.etherscan.io/api'),
            'api_key'  => Setting::get('etherscan_api_key') ?: config('crynova.eth.etherscan_api_key'),
        ];
    }

    private function jsonRpc(string $method, array $params = []): mixed
    {
        $response = Http::timeout(10)->post($this->nodeUrl, [
            'jsonrpc' => '2.0',
            'id'      => 1,
            'method'  => $method,
            'params'  => $params,
        ]);

        $body = $response->json();

        if (isset($body['error'])) {
            throw new RuntimeException("ETH RPC error [{$method}]: " . ($body['error']['message'] ?? 'unknown'));
        }

        return $body['result'];
    }
}
