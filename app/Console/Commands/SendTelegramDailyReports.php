<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;

class SendTelegramDailyReports extends Command
{
    protected $signature = 'crynova:telegram-daily-reports';

    protected $description = 'Send daily merchant summaries to users through Telegram';

    public function handle(TelegramNotificationService $telegram): int
    {
        $users = User::query()
            ->whereNotNull('telegram')
            ->whereHas('merchants')
            ->get();

        foreach ($users as $user) {
            $telegram->notifyDailyReport($user);
        }

        $this->info("Queued Telegram daily reports for {$users->count()} users.");

        return self::SUCCESS;
    }
}
