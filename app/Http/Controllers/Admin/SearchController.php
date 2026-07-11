<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockchainTransaction;
use App\Models\Merchant;
use App\Models\PaymentInvoice;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Global admin search across invoices, merchants, users and transactions.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $invoices = collect();
        $merchants = collect();
        $users = collect();
        $transactions = collect();

        if (mb_strlen($q) >= 2) {
            $like = "%{$q}%";

            $invoices = PaymentInvoice::with('merchant', 'currency')
                ->where('uuid', 'like', $like)
                ->orWhere('order_id', 'like', $like)
                ->orWhere('pay_address', 'like', $like)
                ->latest()
                ->limit(15)
                ->get();

            $merchants = Merchant::with('user')
                ->where('name', 'like', $like)
                ->orWhere('domain', 'like', $like)
                ->orWhere('website', 'like', $like)
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', $like))
                ->limit(15)
                ->get();

            $users = User::where('email', 'like', $like)
                ->orWhere('name', 'like', $like)
                ->limit(15)
                ->get();

            $transactions = BlockchainTransaction::with('invoice')
                ->where('tx_hash', 'like', $like)
                ->orWhere('from_address', 'like', $like)
                ->orWhere('to_address', 'like', $like)
                ->latest()
                ->limit(15)
                ->get();
        }

        $total = $invoices->count() + $merchants->count() + $users->count() + $transactions->count();

        return view('admin.search', compact('q', 'invoices', 'merchants', 'users', 'transactions', 'total'));
    }
}
