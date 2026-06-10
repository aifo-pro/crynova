<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\PaymentInvoice;
use App\Models\Setting;
use App\Models\Wallet;
use App\Services\HdWalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TraceWallet extends Command
{
    protected $signature = 'crynova:trace-wallet
                            {--id= : Wallet id}
                            {--address= : Wallet address}
                            {--show-xpub : Print full current hd_xpub_doge}';

    protected $description = 'Trace how a deposit wallet was created and compare HD xpub used for derivation';

    public function handle(HdWalletService $hd): int
    {
        $wallet = $this->resolveWallet();

        if (! $wallet) {
            $this->error('Wallet not found. Use --id=48 or --address=D7x22…');

            return self::FAILURE;
        }

        $wallet->load(['currency', 'invoice.merchant']);

        $this->info("Wallet #{$wallet->id}");
        $this->table(['Field', 'Value'], [
            ['address', $wallet->address],
            ['hd_path', $wallet->hd_path ?? '—'],
            ['currency', $wallet->currency?->code ?? '—'],
            ['network', $wallet->currency?->network ?? '—'],
            ['type', $wallet->type],
            ['is_used', $wallet->is_used ? 'yes' : 'no'],
            ['invoice_id', $wallet->invoice_id ? (string) $wallet->invoice_id : '—'],
            ['invoice.uuid', $wallet->invoice?->uuid ?? '—'],
            ['created_at', optional($wallet->created_at)->toDateTimeString() ?? '—'],
        ]);

        $this->printCreationStack($wallet);
        $this->printInvoiceAuditTrail($wallet);
        $this->printXpubSources($hd);

        if ($wallet->currency?->network !== 'dogecoin') {
            $this->warn('Not a Dogecoin wallet — HD xpub comparison skipped.');

            return self::SUCCESS;
        }

        $index = $this->parseIndexFromHdPath($wallet->hd_path);
        $this->newLine();
        $this->info('HD path index from wallet.hd_path: '.($index ?? 'unparseable'));

        if ($index !== null && $hd->hasXpub('dogecoin')) {
            $derived = $hd->deriveDogecoinFromStoredXpub($index, normalize: true);
            $matches = strcasecmp($derived['address'], $wallet->address) === 0;

            $this->table(['Check', 'Result'], [
                ['deriveDogecoinFromStoredXpub('.$index.')', $derived['address']],
                ['matches wallet.address', $matches ? 'YES' : 'NO'],
            ]);

            if (! $matches) {
                $this->warn('Current hd_xpub_doge does NOT reproduce this wallet address at stored index.');
                $this->line('Possible causes:');
                $this->line('  • xpub was different when wallet was created (then changed in settings)');
                $this->line('  • address came from dogecoind RPC fallback (hd_path label is still m/44\'/3\'/0\'/0/N)');
                $this->line('  • wallet was reused from pre-generated pool created under another xpub');
            }
        }

        if ((bool) $this->option('show-xpub') && $hd->hasXpub('dogecoin')) {
            $meta = $hd->inspectDogecoinAccountKey();
            $this->newLine();
            $this->line('Current normalized account xpub:');
            $this->line($meta['account_extended_pubkey']);
        }

        $this->newLine();
        $this->comment('Note: Crynova does not store xpub at wallet creation time — only address + hd_path.');

        return self::SUCCESS;
    }

    private function resolveWallet(): ?Wallet
    {
        if ($id = $this->option('id')) {
            return Wallet::find((int) $id);
        }

        if ($address = $this->option('address')) {
            return Wallet::where('address', $address)->first();
        }

        return null;
    }

    private function printCreationStack(Wallet $wallet): void
    {
        $this->newLine();
        $this->info('Code path — Dogecoin address for new invoices');

        $lines = [
            'Entry points (all converge on InvoiceService::create):',
            '  • POST /api/v1/invoices → Api\\V1\\InvoiceController::store',
            '  • Merchant UI → Merchant\\InvoiceController::store',
            '  • Account payments → Account\\PaymentController::store',
            '  • Checkout → CheckoutController (payment link flow)',
            '',
            'InvoiceService::create() [DB::transaction]',
            '  └─ WalletService::assignDepositWallet($currency, $merchant)',
            '       ├─ reuse: Wallet::where(currency, hot, !is_used, invoice_id null)->first()',
            '       └─ OR WalletService::deriveNextAddress()',
            '            ├─ nextIndex = Wallet::where(currency, hot)->count()',
            '            ├─ IF HdWalletService::hasXpub(dogecoin):',
            '            │    HdWalletService::deriveDogecoin($index)',
            '            │      → normalizeExtendedKey(hd_xpub_doge)',
            '            │      → derivePath("0/{index}")',
            '            │      → PayToPubKeyHashAddress (legacy DOGE)',
            '            └─ ELSE DogecoinDriver::deriveAddress($index)',
            '                 → dogecoind RPC getnewaddress (NOT from xpub!)',
            '            └─ WalletService::createFromDerivation()',
            '                 → Wallet::create([address, hd_path, …])',
            '  └─ PaymentInvoice::create([pay_address => wallet.address])',
            '  └─ wallet->update([invoice_id, is_used => true])',
        ];

        if ($wallet->invoice_id) {
            $lines[] = '';
            $lines[] = "This wallet (#{$wallet->id}) is linked to invoice #{$wallet->invoice_id}.";
        } else {
            $lines[] = '';
            $lines[] = 'This wallet has no invoice_id — likely pre-generated pool (crynova:generate-addresses).';
        }

        foreach ($lines as $line) {
            $this->line($line);
        }
    }

    private function printInvoiceAuditTrail(Wallet $wallet): void
    {
        if (! $wallet->invoice) {
            return;
        }

        $invoice = $wallet->invoice;

        $this->newLine();
        $this->info('Invoice / audit trail');

        $this->table(['Field', 'Value'], [
            ['invoice.id', (string) $invoice->id],
            ['invoice.uuid', $invoice->uuid],
            ['pay_address', $invoice->pay_address],
            ['merchant', $invoice->merchant?->name ?? '—'],
            ['created_at', optional($invoice->created_at)->toDateTimeString() ?? '—'],
        ]);

        $logs = AuditLog::query()
            ->where('subject_type', PaymentInvoice::class)
            ->where('subject_id', $invoice->id)
            ->orderBy('id')
            ->get(['id', 'action', 'actor_type', 'actor_ip', 'created_at']);

        if ($logs->isEmpty()) {
            $this->warn('No audit_logs rows found for this invoice.');

            return;
        }

        $this->table(
            ['id', 'action', 'actor', 'ip', 'at'],
            $logs->map(fn ($log) => [
                $log->id,
                $log->action,
                $log->actor_type,
                $log->actor_ip ?? '—',
                optional($log->created_at)->toDateTimeString(),
            ])->all()
        );
    }

    private function printXpubSources(HdWalletService $hd): void
    {
        $this->newLine();
        $this->info('hd_xpub_doge sources & caching');

        $dbRow = DB::table('settings')->where('key', 'hd_xpub_doge')->first();
        $dbValue = null;

        if ($dbRow) {
            $dbValue = $dbRow->is_encrypted
                ? \Illuminate\Support\Facades\Crypt::decryptString($dbRow->value)
                : $dbRow->value;
        }

        $cached = Cache::get('setting:hd_xpub_doge');
        $viaGet = Setting::get('hd_xpub_doge');
        $env = env('HD_XPUB_DOGE');

        $this->table(['Source', 'Present', 'SHA256', 'Prefix'], [
            $this->xpubRow('DB settings table', $dbValue),
            $this->xpubRow('Cache setting:hd_xpub_doge', is_array($cached) ? ($cached['value'] ?? null) : null),
            $this->xpubRow('Setting::get()', is_string($viaGet) ? $viaGet : null),
            $this->xpubRow('env HD_XPUB_DOGE', is_string($env) && trim($env) !== '' ? trim($env) : null),
        ]);

        $this->line('Caching: Setting::get() uses Cache::remember("setting:hd_xpub_doge", TTL=300s).');
        $this->line('Setting::set() calls Cache::forget — direct SQL edits can leave stale cache up to 5 min.');
        $this->line('NOT in config() — HdWalletService has no in-memory xpub field (singleton reads Setting each call).');

        if ($dbValue && is_string($viaGet) && hash('sha256', trim($dbValue)) !== hash('sha256', trim($viaGet))) {
            $this->error('MISMATCH: DB value SHA256 ≠ Setting::get() — cache may be stale. Run: php artisan cache:clear');
        }

        if (! $hd->hasXpub('dogecoin')) {
            $this->warn('No usable DOGE xpub — invoices use dogecoind RPC fallback.');

            return;
        }

        $meta = $hd->inspectDogecoinAccountKey();

        $this->newLine();
        $this->info('Current HdWalletService::inspectDogecoinAccountKey() (used for new derivations)');

        $this->table(['Field', 'Value'], [
            ['source', $meta['source']],
            ['raw SHA256', $meta['raw_sha256']],
            ['normalized SHA256', $meta['normalized_sha256']],
            ['BIP32 depth', (string) $meta['depth'].' (expected '.$meta['expected_account_depth'].')'],
            ['key fingerprint', $meta['key_fingerprint']],
            ['pubkey HASH160', $meta['pubkey_hash160']],
            ['parent fingerprint', $meta['parent_fingerprint']],
            ['chain code SHA256', $meta['chain_code_sha256']],
        ]);

        if ($dbValue && is_string($viaGet)) {
            $same = hash('sha256', trim($dbValue)) === $meta['raw_sha256'];
            $this->line('DB xpub SHA256 matches inspectDogecoinAccountKey raw SHA256: '.($same ? 'YES' : 'NO'));
        }
    }

    /** @return array{0: string, 1: string, 2: string, 3: string} */
    private function xpubRow(string $label, mixed $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [$label, 'no', '—', '—'];
        }

        $trim = trim($value);

        return [$label, 'yes', hash('sha256', $trim), substr($trim, 0, 8).'…'];
    }

    private function parseIndexFromHdPath(?string $path): ?int
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match("/0'\\/0\\/(\\d+)\$/", $path, $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
