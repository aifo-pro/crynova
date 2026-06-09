<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function index(Request $request, Merchant $merchant)
    {
        $dateFrom = $request->input('date_from', now()->format('Y-m-01'));
        $dateTo   = $request->input('date_to',   now()->format('Y-m-d'));

        $baseQuery = $merchant->invoices()
            ->whereIn('status', ['paid', 'overpaid'])
            ->whereBetween('paid_at', [
                $dateFrom . ' 00:00:00',
                $dateTo   . ' 23:59:59',
            ]);

        $paidInvoices = $baseQuery->get();

        // Financial summary using bcmath for precision
        $gross      = '0';
        $fees       = '0';
        $net        = '0';

        foreach ($paidInvoices as $inv) {
            $gross = bcadd($gross, (string) $inv->amount_received, 18);
            $fees  = bcadd($fees,  (string) $inv->fee_amount,     18);
            $net   = bcadd($net,   (string) $inv->net_amount,      18);
        }

        // Daily breakdown for chart
        $dailyData = $merchant->invoices()
            ->selectRaw('DATE(paid_at) as day,
                COUNT(*) as paid_count,
                SUM(COALESCE(amount_received, 0)) as revenue,
                SUM(COALESCE(fee_amount, 0)) as fees')
            ->whereIn('status', ['paid', 'overpaid'])
            ->whereBetween('paid_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Per-currency breakdown
        $byCurrency = $merchant->invoices()
            ->selectRaw('currency_id,
                COUNT(*) as count,
                SUM(COALESCE(amount_received, 0)) as gross,
                SUM(COALESCE(net_amount, 0)) as net')
            ->whereIn('status', ['paid', 'overpaid'])
            ->whereBetween('paid_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->groupBy('currency_id')
            ->with('currency')
            ->get();

        // Recent payouts (withdrawals) in same period
        $withdrawals = $merchant->withdrawals()
            ->with('currency')
            ->whereIn('status', ['completed', 'approved'])
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->latest()
            ->limit(20)
            ->get();

        return view('merchant.settlement.index', compact(
            'merchant', 'dateFrom', 'dateTo',
            'gross', 'fees', 'net',
            'paidInvoices', 'dailyData', 'byCurrency', 'withdrawals'
        ));
    }
}
