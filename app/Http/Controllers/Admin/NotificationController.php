<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Merchant;
use App\Models\Refund;
use App\Models\SupportTicket;
use App\Models\WebhookLog;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Lightweight JSON feed for the header bell — polled by the client.
     */
    public function feed(): JsonResponse
    {
        $items = [
            [
                'label' => 'Мерчанти на модерації',
                'count' => Merchant::where('status', Merchant::STATUS_MODERATION)->count(),
                'url'   => route('admin.merchants.index', ['status' => 'moderation']),
            ],
            [
                'label' => 'Виплати на перевірці',
                'count' => Withdrawal::where('status', 'pending')->count(),
                'url'   => route('admin.withdrawals.index'),
            ],
            [
                'label' => 'Повернення в очікуванні',
                'count' => Refund::where('status', 'pending')->count(),
                'url'   => route('admin.refunds.index'),
            ],
            [
                'label' => 'Нові звернення',
                'count' => ContactMessage::where('status', 'new')->count(),
                'url'   => route('admin.contact.index'),
            ],
            [
                'label' => 'Тікети без відповіді',
                'count' => SupportTicket::where('admin_unread', true)->count(),
                'url'   => route('admin.support.index'),
            ],
            [
                'label' => 'Webhook з помилкою',
                'count' => WebhookLog::where('success', false)->whereNotNull('next_retry_at')->count(),
                'url'   => route('admin.health'),
            ],
        ];

        $active = array_values(array_filter($items, fn ($i) => $i['count'] > 0));

        return response()->json([
            'total' => array_sum(array_column($active, 'count')),
            'items' => $active,
        ]);
    }
}
