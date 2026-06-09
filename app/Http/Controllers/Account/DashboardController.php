<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\PaymentInvoice;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $merchantIds = $user->merchants()->pluck('id');

        // Aggregate invoice stats across all the user's projects
        $base = PaymentInvoice::whereIn('merchant_id', $merchantIds);

        $stats = [
            'balance'   => 0.00, // USD-equivalent settlement balance (wire to FX later)
            'created'   => (clone $base)->count(),
            'paid'      => (clone $base)->where('status', 'paid')->count(),
            'partial'   => (clone $base)->where('status', 'underpaid')->count(),
        ];

        // Chart: created vs paid invoices over the last 7 days
        $days = collect(range(6, 0))->map(fn ($d) => now()->subDays($d)->format('Y-m-d'));
        $createdByDay = (clone $base)
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->get()
            ->groupBy(fn ($i) => $i->created_at->format('Y-m-d'));

        $chart = $days->map(fn ($day) => [
            'day'     => $day,
            'created' => isset($createdByDay[$day]) ? $createdByDay[$day]->count() : 0,
            'paid'    => isset($createdByDay[$day]) ? $createdByDay[$day]->where('status', 'paid')->count() : 0,
        ])->values();

        $recentInvoices = PaymentInvoice::whereIn('merchant_id', $merchantIds)
            ->with(['merchant', 'currency'])
            ->latest()
            ->limit(5)
            ->get();

        $balances = Balance::whereIn('merchant_id', $merchantIds)
            ->with('currency')
            ->get()
            ->groupBy('currency_id')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'currency' => $first->currency,
                    'available' => $rows->sum(fn ($row) => (float) $row->available),
                ];
            })
            ->sortByDesc('available')
            ->take(5)
            ->values();

        return view('account.dashboard', compact('user', 'stats', 'chart', 'recentInvoices', 'balances'));
    }
}
