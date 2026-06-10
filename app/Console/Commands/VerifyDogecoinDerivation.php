<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\Wallet;
use App\Services\HdWalletService;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use Illuminate\Console\Command;
use RuntimeException;

class VerifyDogecoinDerivation extends Command
{
    protected $signature = 'crynova:verify-doge-derivation
                            {--max=1000 : Highest address index to scan (inclusive)}
                            {--find=D5to61GDBznPvWPBcUYgnbqGd7M3GcdAGD : Address to locate in the scan}
                            {--show-all : Print every derived address}
                            {--show-xpub : Print full normalized account xpub from settings}
                            {--try-raw : Also scan using xpub without normalizeExtendedKey()}';

    protected $description = 'Scan DOGE HD derivation from stored xpub and verify invoice addresses';

    public function handle(HdWalletService $hd): int
    {
        if (! $hd->hasXpub('dogecoin')) {
            $this->error('DOGE xpub is not configured (Admin → Settings → hd_xpub_doge or HD_XPUB_DOGE).');

            return self::FAILURE;
        }

        $max = max(0, (int) $this->option('max'));
        $target = strtoupper(trim((string) $this->option('find')));
        $showAll = (bool) $this->option('show-all');
        $showXpub = (bool) $this->option('show-xpub');
        $tryRaw = (bool) $this->option('try-raw');

        $meta = $hd->inspectDogecoinAccountKey();

        $this->info('DOGE account xpub from database');
        $this->table(['Field', 'Value'], [
            ['Source', $meta['source']],
            ['Raw prefix', $meta['raw_prefix'].'… ('.$meta['raw_length'].' chars)'],
            ['Raw SHA256', $meta['raw_sha256']],
            ['Normalized prefix', $meta['normalized_prefix'].'… ('.$meta['normalized_length'].' chars)'],
            ['Normalized SHA256', $meta['normalized_sha256']],
            ['BIP32 depth', $meta['depth'].' (expected '.$meta['expected_account_depth'].' for '.$meta['expected_account_path'].')'],
            ['BIP32 sequence', $meta['sequence_hex'].' ('.$meta['sequence'].')'],
            ['Parent fingerprint', $meta['parent_fingerprint']],
            ['Key fingerprint', $meta['key_fingerprint']],
            ['PubKey HASH160', $meta['pubkey_hash160']],
            ['Chain code SHA256', $meta['chain_code_sha256']],
        ]);

        if ($meta['depth'] !== $meta['expected_account_depth']) {
            $this->warn('Depth is not 3 — xpub may be exported from wrong BIP32 level (not m/44\'/3\'/0\').');
        }

        if ($showXpub) {
            $this->newLine();
            $this->line('Normalized account extended public key:');
            $this->line($meta['account_extended_pubkey']);
        } else {
            $this->line('Tip: use --show-xpub to print the full normalized account key for BIP39 Tool comparison.');
        }

        $this->lookupWalletInDatabase($target);

        $this->newLine();
        $this->info("Scanning index 0..{$max} (normalized xpub, derivePath 0/index)");

        $match = $this->scanRange($hd, 0, $max, true, $target, $showAll);

        if ($tryRaw) {
            $this->newLine();
            $this->info("Scanning index 0..{$max} (RAW xpub, no normalizeExtendedKey)");

            try {
                $rawMatch = $this->scanRange($hd, 0, $max, false, $target, $showAll);
                $match = $match ?? $rawMatch;
            } catch (RuntimeException $e) {
                $this->warn('RAW xpub scan failed: '.$e->getMessage());
            }
        }

        if ($match !== null) {
            $this->newLine();
            $this->info("FOUND {$target} at index {$match['index']} (normalized=".($match['normalized'] ? 'yes' : 'no').')');
            $this->line("Path: {$match['path']}");
        } else {
            $this->newLine();
            $this->error("NOT FOUND: {$target} in index 0..{$max}");

            $this->warn('Likely causes:');
            $this->line('  • xpub in Crynova is not the same account key as in BIP39 Tool');
            $this->line('  • xpub exported from wrong depth (must be m/44\'/3\'/0\')');
            $this->line('  • invoice address came from dogecoind RPC fallback, not HD xpub');
            $this->line('  • compare key fingerprint / pubkey HASH160 with BIP39 Tool Account Extended Public Key');

            $this->tryAlternateDerivations($hd, $target);
        }

        if (! $showAll) {
            $this->newLine();
            $this->line('First 5 addresses (normalized):');
            for ($i = 0; $i <= min(4, $max); $i++) {
                $row = $hd->deriveDogecoinFromStoredXpub($i, normalize: true);
                $this->line(sprintf('  [%4d] %s  %s', $row['index'], $row['address'], $row['path']));
            }
        }

        return $match !== null ? self::SUCCESS : self::FAILURE;
    }

    /** @return array{index: int, path: string, normalized: bool}|null */
    private function scanRange(HdWalletService $hd, int $from, int $to, bool $normalize, string $target, bool $showAll): ?array
    {
        $found = null;

        for ($i = $from; $i <= $to; $i++) {
            $row = $hd->deriveDogecoinFromStoredXpub($i, $normalize);

            if ($showAll) {
                $this->line(sprintf('[%4d] %s  %s', $row['index'], $row['address'], $row['path']));
            }

            if (strtoupper($row['address']) === $target) {
                $found = [
                    'index'      => $i,
                    'path'       => $row['path'],
                    'normalized' => $normalize,
                ];

                if (! $showAll) {
                    break;
                }
            }
        }

        return $found;
    }

    private function lookupWalletInDatabase(string $address): void
    {
        $currency = Currency::where('code', 'DOGE')->first();

        if (! $currency) {
            return;
        }

        $wallet = Wallet::where('currency_id', $currency->id)
            ->where('address', $address)
            ->first();

        $this->newLine();
        $this->info('Wallet row in Crynova DB');

        if (! $wallet) {
            $this->warn("No wallets.address = {$address}");

            return;
        }

        $this->table(['Field', 'Value'], [
            ['wallet.id', (string) $wallet->id],
            ['address', $wallet->address],
            ['hd_path', $wallet->hd_path ?? '—'],
            ['type', $wallet->type],
            ['invoice_id', $wallet->invoice_id ? (string) $wallet->invoice_id : '—'],
            ['created_at', optional($wallet->created_at)->toDateTimeString() ?? '—'],
        ]);
    }

    private function tryAlternateDerivations(HdWalletService $hd, string $target): void
    {
        $this->newLine();
        $this->info('Alternate derivation probes (first 20 indices each)');

        $raw = trim((string) $hd->rawXpub('dogecoin'));
        $network = NetworkFactory::dogecoin();

        $probes = [
            '0/index (normalized)' => fn (int $i) => $hd->deriveDogecoinFromStoredXpub($i, true),
            'index only (normalized)' => function (int $i) use ($hd, $network, $raw) {
                $xpub = $hd->inspectDogecoinAccountKey()['account_extended_pubkey'];
                $key = HierarchicalKeyFactory::fromExtended($xpub, $network)->derivePath((string) $i);

                return $this->dogeRow($i, $key, $network, "m/44'/3'/0'/{$i}?");
            },
        ];

        if ($raw !== '') {
            try {
                $probes['0/index (raw xpub)'] = fn (int $i) => $hd->deriveDogecoinFromStoredXpub($i, false);
            } catch (\Throwable) {
                // ignore
            }
        }

        foreach ($probes as $label => $derive) {
            for ($i = 0; $i <= 20; $i++) {
                try {
                    $row = $derive($i);
                } catch (\Throwable) {
                    $this->warn("{$label}: failed at index {$i}");

                    continue 2;
                }

                if (strtoupper($row['address']) === $target) {
                    $this->info("Match via {$label} at index {$i}: {$row['address']}");

                    return;
                }
            }
            $this->line("  {$label}: no match in 0..20");
        }
    }

    private function dogeRow(int $index, $key, $network, string $path): array
    {
        $address = (new PayToPubKeyHashAddress($key->getPublicKey()->getPubKeyHash()))->getAddress($network);

        return ['index' => $index, 'address' => $address, 'path' => $path];
    }
}
