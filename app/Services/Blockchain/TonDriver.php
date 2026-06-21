<?php

namespace App\Services\Blockchain;

use App\Models\Currency;
use App\Models\Setting;
use App\Services\BlockchainDriverInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * TON driver (watch-only). Like Solana, TON is ed25519, so the platform uses one
 * shared deposit address + a unique text comment (memo) per invoice. This driver
 * reads incoming transfers to that address via the toncenter API and extracts the
 * comment for matching.
 *
 * Supports native TON. (Jetton/token deposits require jetton-wallet resolution
 * and are not covered here.)
 *
 * NOTE: validate against testnet before enabling for real funds.
 */
class TonDriver implements BlockchainDriverInterface
{
    private function api(string $method, array $query = []): mixed
    {
        $base = rtrim((string) config('crynova.ton.api_url', 'https://toncenter.com/api/v2'), '/');
        $apiKey = (string) (Setting::get('ton_api_key') ?: config('crynova.ton.api_key', ''));

        if ($apiKey !== '') {
            $query['api_key'] = $apiKey;
        }

        $res = Http::timeout(20)->get($base . '/' . $method, $query)->json();

        if (! is_array($res) || empty($res['ok'])) {
            throw new RuntimeException('TON API error [' . $method . ']: ' . json_encode($res));
        }

        return $res['result'] ?? null;
    }

    public function deriveAddress(int $index): array
    {
        throw new RuntimeException('TON uses a shared deposit address + memo; per-index derivation is not supported.');
    }

    public function getBalance(string $address): string
    {
        $nanoton = (string) ($this->api('getAddressBalance', ['address' => $address]) ?? '0');

        return bcdiv($nanoton, bcpow('10', '9', 0), 18);
    }

    public function getBlockHeight(): int
    {
        $info = $this->api('getMasterchainInfo');

        return (int) ($info['last']['seqno'] ?? 0);
    }

    public function getTransactions(string $address, int $fromBlock = 0, ?Currency $currency = null): array
    {
        // Jetton (TON token, e.g. USDT-TON): contract_address = jetton master.
        if ($currency?->contract_address) {
            return $this->jettonTransfers($address, $currency);
        }

        $txs = $this->api('getTransactions', ['address' => $address, 'limit' => 30]);
        if (! is_array($txs)) {
            return [];
        }

        $out = [];
        foreach ($txs as $tx) {
            $in = $tx['in_msg'] ?? null;
            if (! $in) {
                continue;
            }

            // Only incoming value transfers (must have a source and positive value).
            $value = (string) ($in['value'] ?? '0');
            if (empty($in['source']) || bccomp($value, '0', 0) <= 0) {
                continue;
            }

            $amount = bcdiv($value, bcpow('10', '9', 0), 18);
            $hash = $tx['transaction_id']['hash'] ?? ($in['body_hash'] ?? null);
            if (! $hash) {
                continue;
            }

            $out[] = [
                'tx_hash'       => $hash,
                'amount'        => $amount,
                // TON has fast finality; a transaction returned by the API is settled.
                'confirmations' => 32,
                'memo'          => $this->extractComment($in),
                'from'          => $in['source'] ?? null,
                'blockindex'    => 0,
                'blocktime'     => (int) ($tx['utime'] ?? time()),
            ];
        }

        return $out;
    }

    /**
     * Incoming Jetton (token) transfers to the shared address, via tonapi.io
     * which returns already-parsed JettonTransfer actions (amount, comment, jetton).
     */
    private function jettonTransfers(string $address, Currency $currency): array
    {
        $base = rtrim((string) config('crynova.ton.tonapi_url', 'https://tonapi.io'), '/');
        $key  = (string) (Setting::get('tonapi_key') ?: config('crynova.ton.tonapi_key', ''));

        $req = Http::timeout(20);
        if ($key !== '') {
            $req = $req->withToken($key);
        }

        $res = $req->get($base . '/v2/accounts/' . $address . '/jettons/history', ['limit' => 30])->json();
        $events = is_array($res) ? ($res['events'] ?? []) : [];

        $masterRaw = $this->normalizeAddr($currency->contract_address);
        $ownerRaw  = $this->normalizeAddr($address);
        $decimals  = $currency->decimals ?? 9;

        $out = [];
        foreach ($events as $event) {
            foreach (($event['actions'] ?? []) as $action) {
                if (($action['type'] ?? null) !== 'JettonTransfer') {
                    continue;
                }
                $jt = $action['JettonTransfer'] ?? [];

                // Must be the right jetton and an incoming transfer to our address.
                if ($this->normalizeAddr($jt['jetton']['address'] ?? '') !== $masterRaw) {
                    continue;
                }
                $recipient = $this->normalizeAddr($jt['recipient']['address'] ?? '');
                if ($ownerRaw !== '' && $recipient !== '' && $recipient !== $ownerRaw) {
                    continue; // outgoing or unrelated
                }

                $raw = (string) ($jt['amount'] ?? '0');
                if (bccomp($raw, '0', 0) <= 0) {
                    continue;
                }

                $out[] = [
                    'tx_hash'       => $event['event_id'] ?? ($jt['transaction_hash'] ?? null),
                    'amount'        => bcdiv($raw, bcpow('10', (string) $decimals, 0), 18),
                    'confirmations' => 32,
                    'memo'          => isset($jt['comment']) && is_string($jt['comment']) ? trim($jt['comment']) : null,
                    'from'          => $jt['sender']['address'] ?? null,
                    'blockindex'    => 0,
                    'blocktime'     => (int) ($event['timestamp'] ?? time()),
                ];
            }
        }

        return array_values(array_filter($out, fn ($t) => ! empty($t['tx_hash'])));
    }

    /** Normalize a TON address for comparison (raw 0:hex form, lowercased). */
    private function normalizeAddr(string $addr): string
    {
        $addr = trim($addr);
        if (str_contains($addr, ':')) {
            return strtolower($addr);
        }

        return $addr; // base64 (EQ/UQ) — compared as-is when raw form unavailable
    }

    /** Plain-text comment carried by an incoming TON message. */
    private function extractComment(array $inMsg): ?string
    {
        $comment = $inMsg['message'] ?? null;
        if (is_string($comment) && $comment !== '') {
            return trim($comment);
        }

        // Some responses expose the text under msg_data.text.
        $text = $inMsg['msg_data']['text'] ?? null;

        return is_string($text) && $text !== '' ? trim($text) : null;
    }

    public function broadcast(string $rawTx): string
    {
        throw new RuntimeException('TON broadcast is not supported in watch-only mode.');
    }
}
