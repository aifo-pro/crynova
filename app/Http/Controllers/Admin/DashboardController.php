<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Merchant;
use App\Models\PaymentInvoice;
use App\Models\Refund;
use App\Models\SupportTicket;
use App\Models\WebhookLog;
use App\Models\Withdrawal;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_merchants'     => Merchant::count(),
            'active_merchants'    => Merchant::where('status', Merchant::STATUS_ACTIVE)->count(),
            'invoices_today'      => PaymentInvoice::whereDate('created_at', today())->count(),
            'paid_today'          => PaymentInvoice::whereDate('created_at', today())->where('status', 'paid')->count(),
            'pending_withdrawals' => Withdrawal::where('status', 'pending')->count(),
        ];

        // Items that need admin action — each links to the relevant screen.
        $stuckInvoices = PaymentInvoice::where('status', 'waiting_confirmations')
            ->where('updated_at', '<', now()->subMinutes(30))
            ->count();

        $attention = [
            [
                'label' => 'Мерчанти на модерації',
                'count' => Merchant::where('status', Merchant::STATUS_MODERATION)->count(),
                'icon'  => 'clock',
                'tone'  => 'amber',
                'url'   => route('admin.merchants.index', ['status' => 'moderation']),
            ],
            [
                'label' => 'Виплати на перевірці',
                'count' => $stats['pending_withdrawals'],
                'icon'  => 'banknote',
                'tone'  => 'blue',
                'url'   => route('admin.withdrawals.index'),
            ],
            [
                'label' => 'Повернення в очікуванні',
                'count' => Refund::where('status', 'pending')->count(),
                'icon'  => 'credit-card',
                'tone'  => 'violet',
                'url'   => route('admin.refunds.index'),
            ],
            [
                'label' => 'Нові звернення',
                'count' => ContactMessage::where('status', 'new')->count(),
                'icon'  => 'bell',
                'tone'  => 'cyan',
                'url'   => route('admin.contact.index'),
            ],
            [
                'label' => 'Тікети без відповіді',
                'count' => SupportTicket::where('admin_unread', true)->count(),
                'icon'  => 'message-circle',
                'tone'  => 'emerald',
                'url'   => route('admin.support.index'),
            ],
            [
                'label' => 'Webhook з помилкою',
                'count' => WebhookLog::where('success', false)->whereNotNull('next_retry_at')->count(),
                'icon'  => 'alert-triangle',
                'tone'  => 'rose',
                'url'   => route('admin.invoices.index'),
            ],
            [
                'label' => 'Завислі рахунки (>30 хв)',
                'count' => $stuckInvoices,
                'icon'  => 'file-text',
                'tone'  => 'slate',
                'url'   => route('admin.invoices.index', ['status' => 'waiting_confirmations']),
            ],
        ];

        // 7-day paid-invoice trend for the real sparkline.
        $trend = collect(range(6, 0))->map(function (int $daysAgo) {
            $day = Carbon::today()->subDays($daysAgo);

            return [
                'label' => $day->format('d.m'),
                'count' => PaymentInvoice::whereDate('paid_at', $day)
                    ->whereIn('status', ['paid', 'overpaid'])
                    ->count(),
            ];
        })->values();

        $recentInvoices = PaymentInvoice::with('merchant', 'currency')
            ->latest()
            ->limit(10)
            ->get();

        $pendingWithdrawals = Withdrawal::with('merchant', 'currency')
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'attention', 'trend', 'recentInvoices', 'pendingWithdrawals'));
    }
}
