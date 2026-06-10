<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\Merchant;
use App\Models\PaymentInvoice;
use App\Models\Setting;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    public function notifyLogin(User $user, ?string $ip = null): void
    {
        if (! $this->userAllows($user, 'event_auth')) {
            return;
        }

        $this->sendToUser($user, implode("\n", array_filter([
            '<b>Crynova</b>',
            'Вхід у ваш акаунт виконано.',
            $ip ? 'IP: '.$this->escape($ip) : null,
            'Час: '.$this->escape(now()->format('d.m.Y H:i')),
        ])));
    }

    public function notifyAdminRegistration(User $user): void
    {
        $en = $this->adminLanguage() === 'en';

        $this->sendToAdmins(implode("\n", $en ? [
            '<b>New registration</b>',
            'User: '.$this->escape($user->name),
            'Email: '.$this->escape($user->email),
            'ID: #'.$user->id,
        ] : [
            '<b>Нова реєстрація</b>',
            'Користувач: '.$this->escape($user->name),
            'Email: '.$this->escape($user->email),
            'ID: #'.$user->id,
        ]));
    }

    public function notifyMerchantCreated(Merchant $merchant): void
    {
        $merchant->loadMissing('user');
        $en = $this->adminLanguage() === 'en';

        $this->sendToAdmins(implode("\n", $en ? [
            '<b>New merchant</b>',
            'Name: '.$this->escape($merchant->name),
            'Owner: '.$this->escape($merchant->user?->email ?? '-'),
            'Status: '.$this->escape($merchant->status),
        ] : [
            '<b>Новий мерчант</b>',
            'Назва: '.$this->escape($merchant->name),
            'Власник: '.$this->escape($merchant->user?->email ?? '-'),
            'Статус: '.$this->escape($merchant->status),
        ]));
    }

    public function notifyMerchantStatus(Merchant $merchant, string $status, ?string $reason = null): void
    {
        $merchant->loadMissing('user');

        if ($merchant->user) {
            $lines = [
                '<b>Статус мерчанта оновлено</b>',
                'Проєкт: '.$this->escape($merchant->name),
                'Статус: '.$this->escape($status),
            ];

            if ($reason) {
                $lines[] = 'Причина: '.$this->escape($reason);
            }

            $this->sendToUser($merchant->user, implode("\n", $lines));
        }
    }

    public function notifyInvoicePaid(PaymentInvoice $invoice): void
    {
        $invoice->loadMissing('merchant.user', 'currency');
        $user = $invoice->merchant?->user;

        if ($user && $this->userAllows($user, 'event_paid')) {
            $this->sendToUser($user, implode("\n", [
                '<b>Рахунок оплачено</b>',
                'Проєкт: '.$this->escape($invoice->merchant?->name ?? '-'),
                'Invoice: '.$this->escape($invoice->order_id ?: $invoice->uuid),
                'Сума: '.$this->escape((string) $invoice->amount_received).' '.$this->escape($invoice->currency?->code ?? ''),
                'Статус: '.$this->escape($invoice->status),
            ]));
        }

        $en = $this->adminLanguage() === 'en';

        $this->sendToAdmins(implode("\n", $en ? [
            '<b>Invoice paid</b>',
            'Merchant: '.$this->escape($invoice->merchant?->name ?? '-'),
            'Invoice: '.$this->escape($invoice->order_id ?: $invoice->uuid),
            'Amount: '.$this->escape((string) $invoice->amount_received).' '.$this->escape($invoice->currency?->code ?? ''),
            'Status: '.$this->escape($invoice->status),
        ] : [
            '<b>Оплачено рахунок</b>',
            'Мерчант: '.$this->escape($invoice->merchant?->name ?? '-'),
            'Invoice: '.$this->escape($invoice->order_id ?: $invoice->uuid),
            'Сума: '.$this->escape((string) $invoice->amount_received).' '.$this->escape($invoice->currency?->code ?? ''),
            'Статус: '.$this->escape($invoice->status),
        ]));
    }

    public function notifyWithdrawalRequested(Withdrawal $withdrawal): void
    {
        $withdrawal->loadMissing('merchant.user', 'currency');
        $user = $withdrawal->merchant?->user;

        if ($user && $this->userAllows($user, 'event_withdraw')) {
            $this->sendToUser($user, implode("\n", [
                '<b>Заявку на виведення створено</b>',
                'Проєкт: '.$this->escape($withdrawal->merchant?->name ?? '-'),
                'Сума: '.$this->escape((string) $withdrawal->amount).' '.$this->escape($withdrawal->currency?->code ?? ''),
                'Статус: '.$this->escape($withdrawal->status),
            ]));
        }

        $en = $this->adminLanguage() === 'en';

        $this->sendToAdmins(implode("\n", $en ? [
            '<b>New withdrawal request</b>',
            'Merchant: '.$this->escape($withdrawal->merchant?->name ?? '-'),
            'Amount: '.$this->escape((string) $withdrawal->amount).' '.$this->escape($withdrawal->currency?->code ?? ''),
            'Address: <code>'.$this->escape($withdrawal->to_address).'</code>',
        ] : [
            '<b>Нова заявка на виведення</b>',
            'Мерчант: '.$this->escape($withdrawal->merchant?->name ?? '-'),
            'Сума: '.$this->escape((string) $withdrawal->amount).' '.$this->escape($withdrawal->currency?->code ?? ''),
            'Адреса: <code>'.$this->escape($withdrawal->to_address).'</code>',
        ]));
    }

    public function notifyWithdrawalReviewed(Withdrawal $withdrawal, string $status, ?string $reason = null): void
    {
        $withdrawal->loadMissing('merchant.user', 'currency');
        $user = $withdrawal->merchant?->user;

        if (! $user || ! $this->userAllows($user, 'event_withdraw')) {
            return;
        }

        $lines = [
            '<b>Статус виведення оновлено</b>',
            'Сума: '.$this->escape((string) $withdrawal->amount).' '.$this->escape($withdrawal->currency?->code ?? ''),
            'Статус: '.$this->escape($status),
        ];

        if ($reason) {
            $lines[] = 'Причина: '.$this->escape($reason);
        }

        $this->sendToUser($user, implode("\n", $lines));
    }

    public function notifyContactMessage(ContactMessage $message): void
    {
        $en = $this->adminLanguage() === 'en';

        $this->sendToAdmins(implode("\n", $en ? [
            '<b>New support message</b>',
            'Name: '.$this->escape($message->name),
            'Email: '.$this->escape($message->email),
            'Subject: '.$this->escape($message->subject),
        ] : [
            '<b>Нове звернення</b>',
            'Ім’я: '.$this->escape($message->name),
            'Email: '.$this->escape($message->email),
            'Тема: '.$this->escape($message->subject),
        ]));
    }

    public function notifySupportTicket(\App\Models\SupportTicket $ticket, bool $reply = false): void
    {
        $en = $this->adminLanguage() === 'en';
        $name = $this->escape((string) optional($ticket->user)->name);
        $subject = $this->escape($ticket->subject);

        $this->sendToAdmins(implode("\n", $en ? [
            '<b>'.($reply ? 'New reply in ticket' : 'New support ticket').' #'.$ticket->id.'</b>',
            'User: '.$name,
            'Subject: '.$subject,
        ] : [
            '<b>'.($reply ? 'Нова відповідь у тікеті' : 'Новий тікет підтримки').' #'.$ticket->id.'</b>',
            'Користувач: '.$name,
            'Тема: '.$subject,
        ]));
    }

    public function notifyDailyReport(User $user): void
    {
        if (! $this->userAllows($user, null)) {
            return;
        }

        $merchantIds = $user->merchants()->pluck('id');
        if ($merchantIds->isEmpty()) {
            return;
        }

        $paidCount = PaymentInvoice::whereIn('merchant_id', $merchantIds)
            ->whereIn('status', ['paid', 'overpaid'])
            ->where('paid_at', '>=', now()->startOfDay())
            ->count();

        $totalInvoices = PaymentInvoice::whereIn('merchant_id', $merchantIds)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        $activeMerchants = Merchant::whereIn('id', $merchantIds)->where('status', Merchant::STATUS_ACTIVE)->count();

        $this->sendToUser($user, implode("\n", [
            '<b>Щоденний звіт Crynova</b>',
            'Активних проєктів: '.$activeMerchants,
            'Рахунків за сьогодні: '.$totalInvoices,
            'Оплачених рахунків: '.$paidCount,
            'Дата: '.$this->escape(now()->format('d.m.Y')),
        ]));
    }

    private function userAllows(User $user, ?string $eventKey): bool
    {
        if (! (bool) Setting::get('telegram_user_notifications_enabled', false)) {
            return false;
        }

        if (trim((string) Setting::get('telegram_user_bot_token', '')) === '') {
            return false;
        }

        if (! $this->userChatId($user)) {
            return false;
        }

        $prefs = array_merge([
            'channel_telegram' => false,
            'event_auth' => true,
            'event_withdraw' => true,
            'event_partial' => true,
            'event_paid' => true,
        ], $user->notification_prefs ?? []);

        if (! (bool) ($prefs['channel_telegram'] ?? false)) {
            return false;
        }

        return $eventKey === null || (bool) ($prefs[$eventKey] ?? true);
    }

    private function sendToUser(User $user, string $message): void
    {
        $token = trim((string) Setting::get('telegram_user_bot_token', ''));
        $chatId = $this->userChatId($user);

        if ($token === '' || ! $chatId) {
            return;
        }

        $this->send($token, $chatId, $message);
    }

    private function sendToAdmins(string $message): void
    {
        if (! (bool) Setting::get('telegram_admin_notifications_enabled', false)) {
            return;
        }

        $token = trim((string) Setting::get('telegram_admin_bot_token', ''));
        if ($token === '') {
            $token = trim((string) Setting::get('telegram_user_bot_token', ''));
        }

        if ($token === '') {
            return;
        }

        foreach ($this->adminChatIds() as $chatId) {
            $this->send($token, $chatId, $message);
        }
    }

    private function send(string $token, string $chatId, string $message): void
    {
        try {
            $response = Http::timeout(8)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if (! $response->successful()) {
                Log::warning('Telegram notification failed', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram notification exception: '.$e->getMessage(), [
                'chat_id' => $chatId,
            ]);
        }
    }

    private function adminChatIds(): array
    {
        $raw = (string) Setting::get('telegram_admin_ids', '');

        return collect(preg_split('/[\s,;]+/', $raw) ?: [])
            ->map(fn ($id) => trim($id))
            ->filter()
            ->values()
            ->all();
    }

    private function adminLanguage(): string
    {
        return (string) Setting::get('telegram_admin_language', 'uk') === 'en' ? 'en' : 'uk';
    }

    private function userChatId(User $user): ?string
    {
        $telegram = trim((string) $user->telegram);

        if ($telegram === '') {
            return null;
        }

        if (preg_match('/^-?\d+$/', $telegram)) {
            return $telegram;
        }

        return '@'.ltrim($telegram, '@');
    }

    private function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
