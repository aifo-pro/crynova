<?php

namespace App\Services\Blockchain;

use App\Models\Currency;
use App\Services\BlockchainDriverInterface;
use App\Services\HdWalletService;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BitcoinDriver implements BlockchainDriverInterface
{
    protected string $rpcUrl;
    protected string $rpcUser;
    protected string $rpcPass;

    public function __construct(
        protected readonly HdWalletService $hdWallet,
    ) {
        $this->rpcUrl  = config('crynova.btc.node_url');
        $this->rpcUser = config('crynova.btc.node_user');
        $this->rpcPass = config('crynova.btc.node_pass');
    }

    public function deriveAddress(int $index): array
    {
        if ($this->hdWallet->hasXpub('bitcoin')) {
            return $this->hdWallet->deriveBitcoin($index);
        }

        $response = $this->rpc('getnewaddress', ["crynova_deposit_{$index}", 'bech32']);

        return [
            'address' => $response,
            'path'    => "m/84'/0'/0'/0/{$index}",
            'memo'    => null,
        ];
    }

    public function getBalance(string $address): string
    {
        $utxos = $this->rpc('listunspent', [1, 9999999, [$address]]);
        $total = array_reduce($utxos, fn ($carry, $utxo) => bcadd($carry, (string) $utxo['amount'], 8), '0');

        return $total;
    }

    public function getTransactions(string $address, int $fromBlock = 0, ?Currency $currency = null): array
    {
        $txs = $this->rpc('listtransactions', ['*', 100, 0, true]);

        return array_values(array_filter($txs, fn ($tx) =>
            ($tx['address'] ?? '') === $address &&
            ($tx['category'] ?? '') === 'receive' &&
            ($tx['confirmations'] ?? 0) >= 0
        ));
    }

    public function broadcast(string $rawTx): string
    {
        return $this->rpc('sendrawtransaction', [$rawTx]);
    }

    public function getBlockHeight(): int
    {
        return (int) $this->rpc('getblockcount');
    }

    protected function rpc(string $method, array $params = []): mixed
    {
        $response = Http::withBasicAuth($this->rpcUser, $this->rpcPass)
            ->timeout(10)
            ->post($this->rpcUrl, [
                'jsonrpc' => '2.0',
                'id'      => 1,
                'method'  => $method,
                'params'  => $params,
            ]);

        $body = $response->json();

        if (! empty($body['error'])) {
            throw new RuntimeException("BTC RPC error [{$method}]: " . $body['error']['message']);
        }

        return $body['result'];
    }
}
