<?php

namespace App\Services\Blockchain;

use App\Models\Currency;
use App\Services\BlockchainDriverInterface;
use App\Services\HdWalletService;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TronDriver implements BlockchainDriverInterface
{
    private string $nodeUrl;
    private string $apiKey;

    public function __construct(
        private readonly HdWalletService $hdWallet,
    ) {
        $this->nodeUrl = config('crynova.tron.node_url', 'https://api.trongrid.io');
        $this->apiKey  = config('crynova.tron.api_key', '');
    }

    public function deriveAddress(int $index): array
    {
        if (! $this->hdWallet->hasXpub('tron')) {
            throw new RuntimeException('TRON HD xpub is not configured. Set hd_xpub_tron in admin settings or HD_XPUB_TRON in .env.');
        }

        return $this->hdWallet->deriveTron($index);
    }

    public function getBalance(string $address): string
    {
        $response = $this->get("/v1/accounts/{$address}");
        $sun      = $response['data'][0]['balance'] ?? 0;

        return bcdiv((string) $sun, '1000000', 6);
    }

    public function getTransactions(string $address, int $fromBlock = 0, ?Currency $currency = null): array
    {
        if ($currency?->contract_address) {
            return $this->getTrc20Transactions($address, $currency, $fromBlock);
        }

        $response = $this->get("/v1/accounts/{$address}/transactions", [
            'limit'                 => 50,
            'min_block_timestamp'   => $fromBlock > 0 ? $fromBlock : 0,
            'only_to'               => 'true',
        ]);

        $currentBlock = $this->getBlockHeight();

        return collect($response['data'] ?? [])
            ->map(function (array $tx) use ($currentBlock, $address) {
                $contract = $tx['raw_data']['contract'][0] ?? null;
                $value = $contract['parameter']['value'] ?? [];
                $to = $value['to_address'] ?? null;

                if ($to && str_starts_with($to, '41')) {
                    $to = $this->hdWallet->tronAddressFromEthHex('0x' . substr($to, 2));
                }

                if ($to !== $address) {
                    return null;
                }

                $block = (int) ($tx['blockNumber'] ?? 0);
                $amount = bcdiv((string) ($value['amount'] ?? 0), '1000000', 6);

                return [
                    'tx_hash'       => $tx['txID'] ?? $tx['txid'] ?? null,
                    'amount'        => $amount,
                    'confirmations' => $block > 0 ? max(0, $currentBlock - $block + 1) : 0,
                    'from'          => null,
                    'blockindex'    => $block,
                    'blocktime'     => (int) (($tx['raw_data']['timestamp'] ?? 0) / 1000),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function broadcast(string $rawTx): string
    {
        $response = $this->post('/wallet/broadcasthex', ['transaction' => $rawTx]);

        if (! ($response['result'] ?? false)) {
            throw new RuntimeException('TRON broadcast failed: ' . json_encode($response));
        }

        return $response['txid'];
    }

    public function getBlockHeight(): int
    {
        $response = $this->post('/wallet/getnowblock', []);

        return (int) ($response['block_header']['raw_data']['number'] ?? 0);
    }

    private function getTrc20Transactions(string $address, Currency $currency, int $fromBlock): array
    {
        $response = $this->get("/v1/accounts/{$address}/transactions/trc20", [
            'limit'                      => 50,
            'contract_address'           => $currency->contract_address,
            'only_to'                    => 'true',
            'min_timestamp'              => $fromBlock > 0 ? $fromBlock : 0,
        ]);

        $currentBlock = $this->getBlockHeight();
        $decimals = $currency->decimals;

        return collect($response['data'] ?? [])
            ->filter(fn ($tx) => ($tx['to'] ?? '') === $address)
            ->map(function (array $tx) use ($currentBlock, $decimals) {
                $block = (int) ($tx['block_number'] ?? 0);
                $amount = bcdiv((string) ($tx['value'] ?? '0'), bcpow('10', (string) $decimals, 0), 18);

                return [
                    'tx_hash'       => $tx['transaction_id'] ?? null,
                    'amount'        => $amount,
                    'confirmations' => $block > 0 ? max(0, $currentBlock - $block + 1) : 0,
                    'from'          => $tx['from'] ?? null,
                    'blockindex'    => $block,
                    'blocktime'     => (int) (($tx['block_timestamp'] ?? 0) / 1000),
                ];
            })
            ->values()
            ->all();
    }

    private function get(string $path, array $query = []): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(10)
            ->get($this->nodeUrl . $path, $query);

        return $response->json() ?? [];
    }

    private function post(string $path, array $body): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(10)
            ->post($this->nodeUrl . $path, $body);

        return $response->json() ?? [];
    }

    private function headers(): array
    {
        $headers = ['Accept' => 'application/json'];

        if ($this->apiKey) {
            $headers['TRON-PRO-API-KEY'] = $this->apiKey;
        }

        return $headers;
    }
}
