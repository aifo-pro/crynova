<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Merchant;
use Illuminate\Http\Request;

class WidgetController extends Controller
{
    public function index(Request $request, Merchant $merchant)
    {
        $currencies = Currency::where('is_active', true)->orderBy('code')->get();
        $apiKey     = $merchant->apiKeys()
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->first();

        return view('merchant.widget.index', compact('merchant', 'currencies', 'apiKey'));
    }
}
