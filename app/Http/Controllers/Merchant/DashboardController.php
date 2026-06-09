<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Merchant;
use App\Models\PaymentInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request, Merchant $merchant)
    {
        $period = (int) $request->input('period', 30);
        if (! in_array($period, [7, 30, 90], true)) {
            $period = 30;
        }

        $since = now()->subDays($period)->startOfDay();

        // ── KPI counts ────────────────────────────────────────────
        $totalInPeriod = $merchant->invoices()
            ->where('created_at', '>=', $since)
            ->count();

        $paidInPeriod = $merchant->invoices()
            ->whereIn('status', ['paid', 'overpaid'])
            ->where('created_at', '>=', $since)
            ->count();

        $pendingCount = $merchant->invoices()
            ->whereIn('status', ['pending', 'waiting_confirmations'])
            ->count();

        $expiredCount = $merchant->invoices()
            ->where('status', 'expired')
            ->where('created_at', '>=', $since)
            ->count();

        $conversionRate = $totalInPeriod > 0
            ? round(($paidInPeriod / $totalInPeriod) * 100, 1)
            : 0.0;

        // ── Revenue today vs period ─────────────────────────────
        $revenueInPeriod = $merchant->invoices()
            ->whereIn('status', ['paid', 'overpaid'])
            ->where('created_at', '>=', $since)
            ->sum('net_amount');

        $revenueToday = $merchant->invoices()
            ->whereIn('status', ['paid', 'overpaid'])
            ->whereDate('paid_at', today())
            ->sum('net_amount');

        $paidToday = $merchant->invoices()
            ->whereIn('status', ['paid', 'overpaid'])
            ->whereDate('paid_at', today())
            ->count();

        // ── Daily revenue for chart ────────────────────────────
        $dailyData = $merchant->invoices()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total,
                SUM(CASE WHEN status IN ("paid","overpaid") THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status IN ("paid","overpaid") THEN COALESCE(net_amount, 0) ELSE 0 END) as revenue')
            ->where('created_at', '>=', $since)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        // Build complete day-by-day array (fill gaps with zeros)
        $chartLabels  = [];
        $chartRevenue = [];
        $chartPaid    = [];
        for ($i = $period - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $chartLabels[]  = now()->subDays($i)->format('d/m');
            $row = $dailyData->get($day);
            $chartRevenue[] = round((float) ($row->revenue ?? 0), 4);
            $chartPaid[]    = (int) ($row->paid_count ?? 0);
        }

        $kpi = [
            'revenue'        => $revenueInPeriod,
            'today_revenue'  => $revenueToday,
            'paid'           => $paidInPeriod,
            'today_paid'     => $paidToday,
            'conversion'     => $conversionRate,
            'total'          => $totalInPeriod,
            'pending'        => $pendingCount,
            'expired'        => $expiredCount,
        ];

        $balances = $merchant->balances()->with('currency')->get();

        $recentInvoices = $merchant->invoices()
            ->with('currency')
            ->latest()
            ->limit(8)
            ->get();

        return view('merchant.dashboard', compact(
            'merchant', 'kpi', 'balances', 'recentInvoices',
            'period', 'chartLabels', 'chartRevenue', 'chartPaid'
        ));
    }
}
