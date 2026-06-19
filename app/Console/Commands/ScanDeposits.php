<?php

namespace App\Console\Commands;

use App\Services\StaticDepositService;
use Illuminate\Console\Command;

class ScanDeposits extends Command
{
    protected $signature   = 'crynova:scan-deposits';
    protected $description = 'Scan static deposit wallets and credit confirmed incoming transfers';

    public function handle(StaticDepositService $deposits): int
    {
        $deposits->scan();

        $this->info('Static deposit scan complete.');

        return self::SUCCESS;
    }
}
