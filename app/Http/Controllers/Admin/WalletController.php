<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $wallets = Wallet::with('currency', 'invoice')
            ->when($request->input('currency'), fn ($q, $c) =>
                $q->whereHas('currency', fn ($cq) => $cq->where('code', $c))
            )
            ->when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate(30);

        return view('admin.wallets.index', compact('wallets'));
    }
}
