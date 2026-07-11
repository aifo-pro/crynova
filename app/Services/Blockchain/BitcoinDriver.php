<?php

namespace App\Services\Blockchain;

use App\Models\Currency;
use App\Services\BlockchainDriverInterface;
use App\Services\HdWalletService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BitcoinDriver implements BlockchainDriverInterface
{
    protected string $rpcUrl;
    protected string $rpcUser;
    protected string $rpcPass;
    protected string $explorerUrl;
    protected ?string $blockchairChain = 'bitcoin';

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
     * Fetch incoming transactions for an address via public block explorers — no
     * self-hosted node required. Tries BlockCypher first, then falls back to
     * Blockchair if it is rate-limited / unavailable (limits are per server IP).
     */
    public function getTransactions(string $address, int $fromBlock = 0, ?Currency $currency = null): array
    {
        try {
            return $this->normalize($this->fetchFromBlockCypher($address));
        } catch (\Throwable $e) {
            Log::warning("BlockCypher failed for {$address}: {$e->getMessage()} — trying Blockchair.");

            return $this->normalize($this->fetchFromBlockchair($address));
        }
    }

    /** Build the final tx array shape expected by PaymentCheckerService. */
    private function normalize(array $byTx): array
    {
        return array_values(array_map(fn ($t) => [
            'txid'          => $t['txid'],
            'amount'        => bcdiv($t['value_sat'], '100000000', 18),
            'confirmations' => (int) $t['confirmations'],
            'blockindex'    => $t['blockindex'],
            'from'          => null,
            'fee'           => 0,
        ], $byTx));
    }

    /** BlockCypher: /addrs/{address} → txrefs + unconfirmed_txrefs. */
    private function fetchFromBlockCypher(string $address): array
    {
        $query = [];
        if ($token = config('crynova.blockcypher_token')) {
            $query['token'] = $token;
        }

        $response = Http::timeout(15)->get("{$this->explorerUrl}/addrs/{$address}", $query);

        if (! $response->successful()) {
            throw new RuntimeException("BlockCypher HTTP {$response->status()}");
        }

        $data = $response->json();
        $refs = array_merge($data['txrefs'] ?? [], $data['unconfirmed_txrefs'] ?? []);

        $byTx = [];
        foreach ($refs as $ref) {
            if (($ref['tx_input_n'] ?? 0) !== -1) {
                continue; // received outputs only
            }
            $hash = $ref['tx_hash'] ?? null;
            if (! $hash) {
                continue;
            }
            $byTx[$hash] ??= ['txid' => $hash, 'value_sat' => '0', 'confirmations' => 0, 'blockindex' => $ref['block_height'] ?? null];
            $byTx[$hash]['value_sat']     = bcadd($byTx[$hash]['value_sat'], (string) ($ref['value'] ?? '0'), 0);
            $byTx[$hash]['confirmations'] = max($byTx[$hash]['confirmations'], (int) ($ref['confirmations'] ?? 0));
        }

        return $byTx;
    }

    /** Blockchair fallback: /dashboards/address/{address} → utxo + context height. */
    private function fetchFromBlockchair(string $address): array
    {
        $chain = $this->blockchairChain;
        if (! $chain) {
            throw new RuntimeException('No Blockchair chain configured.');
        }

        $query = [];
        if ($key = config('crynova.blockchair_key')) {
            $query['key'] = $key;
        }

        $response = Http::timeout(15)->get("https://api.blockchair.com/{$chain}/dashboards/address/{$address}", $query);

        if (! $response->successful()) {
            throw new RuntimeException("Blockchair HTTP {$response->status()}");
        }

        $data   = $response->json();
        $height = (int) ($data['context']['state'] ?? 0);
        $utxos  = $data['data'][$address]['utxo'] ?? [];

        $byTx = [];
        foreach ($utxos as $utxo) {
            $hash = $utxo['transaction_hash'] ?? null;
            if (! $hash) {
                continue;
            }
            $blockId = (int) ($utxo['block_id'] ?? -1);
            $conf    = ($blockId > 0 && $height > 0) ? max(0, $height - $blockId + 1) : 0;

            $byTx[$hash] ??= ['txid' => $hash, 'value_sat' => '0', 'confirmations' => 0, 'blockindex' => $blockId > 0 ? $blockId : null];
            $byTx[$hash]['value_sat']     = bcadd($byTx[$hash]['value_sat'], (string) ($utxo['value'] ?? '0'), 0);
            $byTx[$hash]['confirmations'] = max($byTx[$hash]['confirmations'], $conf);
        }

        return $byTx;
    }

    public function broadcast(string $rawTx): string
    {
        return $this->rpc('sendrawtransaction', [$rawTx]);
    }

    public function getBlockHeight(): int
    {
        return (int) $this->rpc('getblockcount');
    }

    /**
     * Health probe for the actual deposit-detection path (public block explorer),
     * not the optional self-hosted RPC node. Returns ['ok', 'height', 'error'].
     */
    public function explorerHealth(?Currency $currency = null): array
    {
        if (trim((string) $this->explorerUrl) === '') {
            return ['ok' => false, 'height' => null, 'error' => 'Explorer URL не налаштовано.'];
        }

        try {
            $query = [];
            if ($token = config('crynova.blockcypher_token')) {
                $query['token'] = $token;
            }

            // BlockCypher base endpoint returns chain info incl. current height.
            $response = Http::timeout(8)->get($this->explorerUrl, $query);

            if (! $response->successful()) {
                return ['ok' => false, 'height' => null, 'error' => "Explorer HTTP {$response->status()}"];
            }

            $height = (int) ($response->json('height') ?? 0);

            return ['ok' => $height > 0, 'height' => $height, 'error' => $height > 0 ? null : 'Explorer не повернув висоту.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'height' => null, 'error' => $e->getMessage()];
        }
    }

    protected function rpc(string $method, array $params = []): mixed
    {
        // Watch-only deployments derive addresses from the public xpub and never
        // need a node. If no node URL is configured, fail fast with a clear
        // message instead of hanging on an unreachable/empty endpoint.
        if (trim((string) $this->rpcUrl) === '') {
            throw new RuntimeException('No node RPC configured and no public xpub set for this currency. Add the account xpub in admin settings to derive addresses (watch-only), or configure a node.');
        }

        $response = Http::withBasicAuth($this->rpcUser, $this->rpcPass)
            ->connectTimeout(5)
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
