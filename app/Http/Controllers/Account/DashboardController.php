<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\PaymentInvoice;
use App\Services\RateService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, RateService $rates)
    {
        $user = $request->user();
        $merchantIds = $user->accessibleMerchantIds();

        // Aggregate invoice stats across all the user's projects
        $base = PaymentInvoice::whereIn('merchant_id', $merchantIds);

        // All balances across the user's projects, grouped per currency.
        $grouped = Balance::whereIn('merchant_id', $merchantIds)
            ->with('currency')
            ->get()
            ->groupBy('currency_id')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'currency'  => $first->currency,
                    'available' => $rows->sum(fn ($row) => (float) $row->available),
                ];
            });

        // Total settlement balance converted to USD.
        $usdBalance = $grouped->reduce(function ($carry, $row) use ($rates) {
            $price = $row['currency'] ? $rates->usdPrice($row['currency']->code) : null;

            return $carry + ($row['available'] * ($price ?? 0));
        }, 0.0);

        $stats = [
            'balance'   => round($usdBalance, 2),
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

        $balances = $grouped->sortByDesc('available')->take(5)->values();

        return view('account.dashboard', compact('user', 'stats', 'chart', 'recentInvoices', 'balances'));
    }
}
