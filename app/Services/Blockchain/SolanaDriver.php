<?php

namespace App\Services\Blockchain;

use App\Models\Currency;
use App\Models\Setting;
use App\Services\BlockchainDriverInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Solana driver (watch-only). Solana uses ed25519, so deposit addresses cannot
 * be derived from a public key — the platform uses ONE shared deposit address
 * (operator's public wallet) and a unique memo per invoice. This driver reads
 * incoming transfers to that address and extracts the SPL Memo for matching.
 *
 * Supports native SOL and SPL tokens (currency->contract_address = mint).
 *
 * NOTE: validate against devnet/mainnet before enabling for real funds.
 */
class SolanaDriver implements BlockchainDriverInterface
{
    private const MEMO_PROGRAM = 'MemoSq4gqABAXKb96qnH8TysNcWxMyWCqXgDLGmfcHr';

    private function rpc(string $method, array $params = []): mixed
    {
        $url = (string) (Setting::get('solana_node_url') ?: config('crynova.solana.node_url', 'https://api.mainnet-beta.solana.com'));

        $res = Http::timeout(20)->post($url, [
            'jsonrpc' => '2.0',
            'id'      => 1,
            'method'  => $method,
            'params'  => $params,
        ])->json();

        if (isset($res['error'])) {
            throw new RuntimeException('Solana RPC error [' . $method . ']: ' . ($res['error']['message'] ?? 'unknown'));
        }

        return $res['result'] ?? null;
    }

    public function deriveAddress(int $index): array
    {
        throw new RuntimeException('Solana uses a shared deposit address + memo; per-index derivation is not supported.');
    }

    public function getBalance(string $address): string
    {
        $result = $this->rpc('getBalance', [$address]);
        $lamports = (string) ($result['value'] ?? '0');

        return bcdiv($lamports, bcpow('10', '9', 0), 18);
    }

    public function getBlockHeight(): int
    {
        return (int) ($this->rpc('getSlot') ?? 0);
    }

    public function getTransactions(string $address, int $fromBlock = 0, ?Currency $currency = null): array
    {
        $signatures = $this->rpc('getSignaturesForAddress', [$address, ['limit' => 30]]);
        if (! is_array($signatures)) {
            return [];
        }

        $mint = $currency?->contract_address;
        $decimals = $currency?->decimals ?? ($mint ? 6 : 9);
        $out = [];

        foreach ($signatures as $sig) {
            if (! empty($sig['err']) || empty($sig['signature'])) {
                continue;
            }

            $tx = $this->rpc('getTransaction', [
                $sig['signature'],
                ['encoding' => 'jsonParsed', 'maxSupportedTransactionVersion' => 0, 'commitment' => 'confirmed'],
            ]);
            if (! is_array($tx)) {
                continue;
            }

            $amount = $mint
                ? $this->tokenDelta($tx, $address, $mint, $decimals)
                : $this->nativeDelta($tx, $address);

            if (bccomp($amount, '0', 18) <= 0) {
                continue; // not an incoming transfer to us
            }

            $status = $sig['confirmationStatus'] ?? 'processed';
            $confirmations = $status === 'finalized' ? 32 : ($status === 'confirmed' ? 1 : 0);

            $out[] = [
                'tx_hash'       => $sig['signature'],
                'amount'        => $amount,
                'confirmations' => $confirmations,
                'memo'          => $this->extractMemo($tx),
                'from'          => null,
                'blockindex'    => (int) ($sig['slot'] ?? 0),
                'blocktime'     => (int) ($sig['blockTime'] ?? time()),
            ];
        }

        return $out;
    }

    /** Native SOL credited to $address (lamports delta). */
    private function nativeDelta(array $tx, string $address): string
    {
        $keys = $tx['transaction']['message']['accountKeys'] ?? [];
        $pre  = $tx['meta']['preBalances'] ?? [];
        $post = $tx['meta']['postBalances'] ?? [];

        foreach ($keys as $i => $key) {
            $pubkey = is_array($key) ? ($key['pubkey'] ?? null) : $key;
            if ($pubkey === $address && isset($pre[$i], $post[$i])) {
                $delta = bcsub((string) $post[$i], (string) $pre[$i], 0);
                return bccomp($delta, '0', 0) > 0 ? bcdiv($delta, bcpow('10', '9', 0), 18) : '0';
            }
        }

        return '0';
    }

    /** SPL token credited to $address for a given mint (token-balance delta). */
    private function tokenDelta(array $tx, string $address, string $mint, int $decimals): string
    {
        $pre  = $tx['meta']['preTokenBalances'] ?? [];
        $post = $tx['meta']['postTokenBalances'] ?? [];

        $amt = function (array $rows) use ($address, $mint): string {
            foreach ($rows as $r) {
                if (($r['owner'] ?? null) === $address && ($r['mint'] ?? null) === $mint) {
                    return (string) ($r['uiTokenAmount']['amount'] ?? '0');
                }
            }
            return '0';
        };

        $delta = bcsub($amt($post), $amt($pre), 0);

        return bccomp($delta, '0', 0) > 0 ? bcdiv($delta, bcpow('10', (string) $decimals, 0), 18) : '0';
    }

    /** Extract the SPL Memo program text from a parsed transaction. */
    private function extractMemo(array $tx): ?string
    {
        $instructions = $tx['transaction']['message']['instructions'] ?? [];
        foreach ($instructions as $ix) {
            if (($ix['program'] ?? null) === 'spl-memo' && isset($ix['parsed'])) {
                return is_string($ix['parsed']) ? $ix['parsed'] : null;
            }
            if (($ix['programId'] ?? null) === self::MEMO_PROGRAM && isset($ix['parsed'])) {
                return is_string($ix['parsed']) ? $ix['parsed'] : null;
            }
        }

        // Fallback: parse from log messages ("Program log: Memo (len N): \"text\"").
        foreach (($tx['meta']['logMessages'] ?? []) as $log) {
            if (preg_match('/Memo \(len \d+\): "(.*)"/', (string) $log, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    public function broadcast(string $rawTx): string
    {
        // Watch-only deployment: outgoing transfers are not signed by the platform.
        throw new RuntimeException('Solana broadcast is not supported in watch-only mode.');
    }
}
