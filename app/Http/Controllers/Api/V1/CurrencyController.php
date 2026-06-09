<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->get('_api_key');

        if (! $apiKey->hasPermission('currencies.read')) {
            return response()->json(['error' => 'Permission denied.'], 403);
        }

        $currencies = Currency::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn (Currency $currency) => [
                'code' => $currency->code,
                'name' => $currency->name,
                'network' => $currency->network,
                'contract_address' => $currency->contract_address,
                'decimals' => $currency->decimals,
                'confirmations_required' => $currency->confirmations_required,
                'min_amount' => (string) $currency->min_amount,
                'max_amount' => $currency->max_amount !== null ? (string) $currency->max_amount : null,
                'estimated_fee' => $currency->estimated_fee !== null ? (string) $currency->estimated_fee : null,
                'supports_memo' => $currency->supports_memo,
            ]);

        return response()->json(['data' => $currencies]);
    }
}
