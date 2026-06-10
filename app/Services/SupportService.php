<?php

namespace App\Services;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SupportService
{
    public const MAX_FILES = 5;
    public const MAX_SIZE_KB = 10240; // 10 MB per file

    public function __construct(
        private readonly TelegramNotificationService $telegram,
    ) {}

    /** Create a ticket with its first message + optional attachments. */
    public function createTicket(User $user, string $subject, string $body, array $files = []): SupportTicket
    {
        return DB::transaction(function () use ($user, $subject, $body, $files) {
            $ticket = SupportTicket::create([
                'user_id'         => $user->id,
                'subject'         => $subject,
                'status'          => 'open',
                'last_message_at' => now(),
                'admin_unread'    => true,
                'user_unread'     => false,
            ]);

            $this->storeMessage($ticket, $user, $body, false, $files);
            $this->telegram->notifySupportTicket($ticket->fresh('user'));

            return $ticket;
        });
    }

    /** Post a message to a ticket from either the user or an admin. */
    public function postMessage(SupportTicket $ticket, User $sender, bool $isAdmin, string $body, array $files = []): SupportMessage
    {
        return DB::transaction(function () use ($ticket, $sender, $isAdmin, $body, $files) {
            $message = $this->storeMessage($ticket, $sender, $body, $isAdmin, $files);

            $ticket->update([
                'last_message_at' => now(),
                'status'          => $isAdmin ? 'answered' : ($ticket->isClosed() ? 'open' : 'open'),
                'admin_unread'    => $isAdmin ? false : true,
                'user_unread'     => $isAdmin ? true : false,
            ]);

            if (! $isAdmin) {
                $this->telegram->notifySupportTicket($ticket->fresh('user'), reply: true);
            }

            return $message;
        });
    }

    private function storeMessage(SupportTicket $ticket, User $sender, string $body, bool $isAdmin, array $files): SupportMessage
    {
        $message = $ticket->messages()->create([
            'user_id'  => $sender->id,
            'is_admin' => $isAdmin,
            'body'     => trim($body) !== '' ? $body : null,
        ]);

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $path = $file->store('support/' . $ticket->id, 'public');
            $message->attachments()->create([
                'path'          => $path,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                'mime'          => $file->getClientMimeType(),
                'size'          => $file->getSize(),
            ]);
        }

        return $message;
    }
}
