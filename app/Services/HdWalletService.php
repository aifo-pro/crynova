<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Setting;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Address\SegwitAddress;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;
use kornrunner\Keccak;
use RuntimeException;

/**
 * Derives deposit addresses from HD account xpubs stored in settings (encrypted) or .env.
 * Private keys never touch the application — only extended PUBLIC keys.
 */
class HdWalletService
{
    public function hasXpub(string $network): bool
    {
        return (bool) $this->xpubFor($network);
    }

    public function deriveForCurrency(Currency $currency, int $index): array
    {
        return match ($currency->network) {
            'bitcoin'  => $this->deriveBitcoin($index),
            'litecoin' => $this->deriveLitecoin($index),
            'dogecoin' => $this->deriveDogecoin($index),
            'ethereum', 'bsc' => $this->deriveEthereum($index),
            'tron'     => $this->deriveTron($index),
            default    => throw new RuntimeException("HD derivation not supported for network: {$currency->network}"),
        };
    }

    public function deriveBitcoin(int $index): array
    {
        $network = NetworkFactory::bitcoin();
        $xpub = $this->normalizeExtendedKey($this->requireXpub('bitcoin'), $network->getHDPubByte());
        $key = HierarchicalKeyFactory::fromExtended($xpub, $network)->derivePath("0/{$index}");
        $address = $this->segwitOrP2pkhAddress($key, $network);

        return ['address' => $address, 'path' => "m/84'/0'/0'/0/{$index}", 'memo' => null];
    }

    public function deriveLitecoin(int $index): array
    {
        $network = NetworkFactory::litecoin();
        $xpub = $this->normalizeExtendedKey($this->requireXpub('litecoin'), $network->getHDPubByte());
        $key = HierarchicalKeyFactory::fromExtended($xpub, $network)->derivePath("0/{$index}");
        $address = $this->segwitOrP2pkhAddress($key, $network);

        return ['address' => $address, 'path' => "m/84'/2'/0'/0/{$index}", 'memo' => null];
    }

    public function deriveDogecoin(int $index): array
    {
        $network = NetworkFactory::dogecoin();
        $xpub = $this->normalizeExtendedKey($this->requireXpub('dogecoin'), $network->getHDPubByte());
        $key = HierarchicalKeyFactory::fromExtended($xpub, $network)->derivePath("0/{$index}");
        $address = (new PayToPubKeyHashAddress($key->getPublicKey()->getPubKeyHash()))->getAddress($network);

        return ['address' => $address, 'path' => "m/44'/3'/0'/0/{$index}", 'memo' => null];
    }

    public function deriveEthereum(int $index): array
    {
        $xpub = $this->normalizeExtendedKey($this->requireXpub('ethereum'), NetworkFactory::bitcoin()->getHDPubByte());
        $key = HierarchicalKeyFactory::fromExtended($xpub)->derivePath("0/{$index}");

        return [
            'address' => $this->ethAddressFromPublicKey($key->getPublicKey()->getHex()),
            'path'    => "m/44'/60'/0'/0/{$index}",
            'memo'    => null,
        ];
    }

    public function deriveTron(int $index): array
    {
        $xpub = $this->normalizeExtendedKey($this->requireXpub('tron'), NetworkFactory::bitcoin()->getHDPubByte());
        $key = HierarchicalKeyFactory::fromExtended($xpub)->derivePath("0/{$index}");
        $ethStyle = $this->ethAddressFromPublicKey($key->getPublicKey()->getHex());

        return [
            'address' => $this->tronAddressFromEthHex($ethStyle),
            'path'    => "m/44'/195'/0'/0/{$index}",
            'memo'    => null,
        ];
    }

    public function ethAddressFromPublicKey(string $publicKeyHex): string
    {
        $hex = ltrim($publicKeyHex, '0x');
        if (str_starts_with($hex, '04')) {
            $hex = substr($hex, 2);
        }
        $hash = Keccak::hash(hex2bin($hex), 256);
        $address = '0x' . substr($hash, -40);

        return $this->checksumEthAddress($address);
    }

    public function tronAddressFromEthHex(string $ethAddress): string
    {
        $hex = '41' . substr(strtolower(ltrim($ethAddress, '0x')), -40);

        return $this->base58CheckEncode(hex2bin($hex));
    }

    private function segwitOrP2pkhAddress($key, $network): string
    {
        try {
            $wp = WitnessProgram::v0($key->getPublicKey()->getPubKeyHash());

            return (new SegwitAddress($wp))->getAddress($network);
        } catch (\Throwable) {
            return (new PayToPubKeyHashAddress($key->getPublicKey()->getPubKeyHash()))->getAddress($network);
        }
    }

    private function checksumEthAddress(string $address): string
    {
        $lower = strtolower(ltrim($address, '0x'));
        $hash = Keccak::hash(strtolower($lower), 256);
        $checksummed = '0x';

        for ($i = 0; $i < 40; $i++) {
            $char = $lower[$i];
            $checksummed .= (int) $hash[$i] >= 8 ? strtoupper($char) : $char;
        }

        return $checksummed;
    }

    /**
     * Accept any SLIP-132 extended PUBLIC key (xpub/ypub/zpub/Ltub/Mtub/dgub/…)
     * and re-version it to the prefix the target network expects, so BitWasp can
     * parse it. Only the 4 version bytes change — the key node is identical, so the
     * derived public keys (and thus addresses) are unaffected. The address TYPE
     * (segwit vs legacy) is decided by the derivation code, not by the key prefix.
     */
    private function normalizeExtendedKey(string $key, string $versionHex): string
    {
        $full = $this->base58Decode(trim($key));

        if (strlen($full) < 5) {
            throw new RuntimeException('Invalid extended key.');
        }

        $payload  = substr($full, 0, -4);
        $checksum = substr($full, -4);
        $expected = substr(hash('sha256', hash('sha256', $payload, true), true), 0, 4);

        if (! hash_equals($expected, $checksum)) {
            throw new RuntimeException('Invalid extended key checksum.');
        }

        // Swap the 4-byte version prefix for the network's expected xpub version.
        $reversioned = hex2bin($versionHex) . substr($payload, 4);

        return $this->base58CheckEncode($reversioned);
    }

    private function base58Decode(string $string): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $num = gmp_init(0);

        foreach (str_split($string) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                throw new RuntimeException('Invalid base58 character in extended key.');
            }
            $num = gmp_add(gmp_mul($num, 58), $pos);
        }

        $hex = gmp_strval($num, 16);
        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }
        $bytes = $hex === '0' ? '' : hex2bin($hex);

        // Preserve leading zero bytes (each encoded as '1').
        foreach (str_split($string) as $char) {
            if ($char !== '1') {
                break;
            }
            $bytes = "\x00" . $bytes;
        }

        return $bytes;
    }

    private function base58CheckEncode(string $payload): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $checksum = substr(hash('sha256', hash('sha256', $payload, true), true), 0, 4);
        $data = $payload . $checksum;
        $num = gmp_init(bin2hex($data), 16);
        $encoded = '';

        while (gmp_cmp($num, 0) > 0) {
            [$num, $rem] = gmp_div_qr($num, 58);
            $encoded = $alphabet[gmp_intval($rem)] . $encoded;
        }

        foreach (str_split($data) as $byte) {
            if ($byte !== "\x00") {
                break;
            }
            $encoded = '1' . $encoded;
        }

        return $encoded;
    }

    private function xpubFor(string $network): ?string
    {
        $map = [
            'bitcoin'  => ['hd_xpub_btc', 'HD_XPUB_BTC'],
            'litecoin' => ['hd_xpub_ltc', 'HD_XPUB_LTC'],
            'dogecoin' => ['hd_xpub_doge', 'HD_XPUB_DOGE'],
            'ethereum' => ['hd_xpub_eth', 'HD_XPUB_ETH'],
            'tron'     => ['hd_xpub_tron', 'HD_XPUB_TRON'],
        ];

        if (! isset($map[$network])) {
            return null;
        }

        [$settingKey, $envKey] = $map[$network];
        $value = Setting::get($settingKey);

        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        $env = env($envKey);

        return is_string($env) && trim($env) !== '' ? trim($env) : null;
    }

    private function requireXpub(string $network): string
    {
        $xpub = $this->xpubFor($network);

        if (! $xpub) {
            throw new RuntimeException(
                "HD xpub for {$network} is not configured. Set it in Admin → Settings → Blockchain wallets or via .env (HD_XPUB_*)."
            );
        }

        return $xpub;
    }
}
