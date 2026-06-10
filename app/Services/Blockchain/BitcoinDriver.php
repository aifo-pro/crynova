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
    protected string $explorerUrl;

    public function __construct(
        protected readonly HdWalletService $hdWallet,
    ) {
        $this->rpcUrl      = config('crynova.btc.node_url');
        $this->rpcUser     = config('crynova.btc.node_user');
        $this->rpcPass     = config('crynova.btc.node_pass');
        $this->explorerUrl = config('crynova.btc.explorer_url');
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

    /**
     * Fetch incoming transactions for an address via a public block explorer
     * (BlockCypher by default) — no self-hosted node required. Works for any
     * UTXO chain (BTC/LTC/DOGE) by pointing $explorerUrl at the right coin.
     */
    public function getTransactions(string $address, int $fromBlock = 0, ?Currency $currency = null): array
    {
        $query = [];
        if ($token = config('crynova.blockcypher_token')) {
            $query['token'] = $token;
        }

        $response = Http::timeout(15)->get("{$this->explorerUrl}/addrs/{$address}", $query);

        if (! $response->successful()) {
            throw new RuntimeException("Explorer error for {$address}: HTTP {$response->status()}");
        }

        $data = $response->json();
        $refs = array_merge($data['txrefs'] ?? [], $data['unconfirmed_txrefs'] ?? []);

        // Aggregate received outputs per transaction (tx_input_n == -1 = output to us).
        $byTx = [];
        foreach ($refs as $ref) {
            if (($ref['tx_input_n'] ?? 0) !== -1) {
                continue; // skip spends, keep only received outputs
            }

            $hash = $ref['tx_hash'] ?? null;
            if (! $hash) {
                continue;
            }

            $value = (string) ($ref['value'] ?? '0'); // satoshi-style (1e8)
            if (! isset($byTx[$hash])) {
                $byTx[$hash] = [
                    'txid'          => $hash,
                    'value_sat'     => '0',
                    'confirmations' => (int) ($ref['confirmations'] ?? 0),
                    'blockindex'    => $ref['block_height'] ?? null,
                ];
            }
            $byTx[$hash]['value_sat'] = bcadd($byTx[$hash]['value_sat'], $value, 0);
            $byTx[$hash]['confirmations'] = max($byTx[$hash]['confirmations'], (int) ($ref['confirmations'] ?? 0));
        }

        return array_values(array_map(fn ($t) => [
            'txid'          => $t['txid'],
            'amount'        => bcdiv($t['value_sat'], '100000000', 18),
            'confirmations' => $t['confirmations'],
            'blockindex'    => $t['blockindex'],
            'from'          => null,
            'fee'           => 0,
        ], $byTx));
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
