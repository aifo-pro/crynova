<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $wallets = Wallet::with('currency', 'invoice')
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('address', 'like', "%{$search}%")
                        ->orWhere('memo', 'like', "%{$search}%")
                        ->orWhereHas('invoice', fn ($iq) => $iq->where('uuid', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('currency'), fn ($q, $c) =>
                $q->whereHas('currency', fn ($cq) => $cq->where('code', $c))
            )
            ->when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
            ->when($request->input('status') === 'free', fn ($q) => $q->where('is_used', false))
            ->when($request->input('status') === 'used', fn ($q) => $q->where('is_used', true))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $currencies = Currency::orderBy('code')->get();
        $types = Wallet::query()
            ->select('type')
            ->distinct()
            ->whereNotNull('type')
            ->pluck('type')
            ->sort()
            ->values();
        $stats = [
            'total' => Wallet::count(),
            'free' => Wallet::where('is_used', false)->count(),
            'used' => Wallet::where('is_used', true)->count(),
            'hot' => Wallet::where('type', 'hot')->count(),
        ];

        return view('admin.wallets.index', compact('wallets', 'currencies', 'types', 'stats'));
    }
}
