<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Currency;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaticWalletController extends Controller
{
    public function __construct(private readonly WalletService $wallets) {}

    // GET /api/v1/static-wallets
    public function index(Request $request): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->get('_api_key');

        if (! $apiKey->hasPermission('wallets.read')) {
            return response()->json(['error' => 'Permission denied.'], 403);
        }

        $list = Wallet::with('currency')
            ->where('merchant_id', $apiKey->merchant_id)
            ->where('type', 'static')
            ->get()
            ->map(fn (Wallet $w) => $this->payload($w))
            ->values()
            ->all();

        return response()->json(['data' => $list]);
    }

    // POST /api/v1/static-wallets  — get-or-create a permanent deposit address.
    public function store(Request $request): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->get('_api_key');

        if (! $apiKey->hasPermission('wallets.create')) {
            return response()->json(['error' => 'Permission denied.'], 403);
        }

        $validated = $request->validate([
            'currency' => ['required', 'string', 'max:32'],
        ]);

        $currency = Currency::where('code', strtoupper($validated['currency']))->where('is_active', true)->first();
        if (! $currency) {
            return response()->json(['error' => 'Unknown or inactive currency.'], 422);
        }

        try {
            $wallet = $this->wallets->staticWalletFor($currency, $apiKey->merchant);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($this->payload($wallet->load('currency')), 201);
    }

    private function payload(Wallet $w): array
    {
        return [
            'currency' => optional($w->currency)->code,
            'network'  => optional($w->currency)->network,
            'address'  => $w->address,
            'memo'     => $w->memo,
        ];
    }
}
