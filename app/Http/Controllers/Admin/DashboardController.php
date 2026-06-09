<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\PaymentInvoice;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_merchants'   => Merchant::count(),
            'active_merchants'  => Merchant::where('is_active', true)->count(),
            'invoices_today'    => PaymentInvoice::whereDate('created_at', today())->count(),
            'paid_today'        => PaymentInvoice::whereDate('created_at', today())->where('status', 'paid')->count(),
            'pending_withdrawals' => Withdrawal::where('status', 'pending')->count(),
        ];

        $recentInvoices = PaymentInvoice::with('merchant', 'currency')
            ->latest()
            ->limit(10)
            ->get();

        $pendingWithdrawals = Withdrawal::with('merchant', 'currency')
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentInvoices', 'pendingWithdrawals'));
    }
}
