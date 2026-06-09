<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockchainTransaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $txs = BlockchainTransaction::with('currency', 'invoice.merchant')
            ->when($request->input('search'), fn ($q, $s) =>
                $q->where('tx_hash', 'like', "%{$s}%")->orWhere('to_address', 'like', "%{$s}%")
            )
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(30);

        return view('admin.transactions.index', compact('txs'));
    }
}
