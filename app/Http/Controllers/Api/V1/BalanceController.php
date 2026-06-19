<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Balance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    // GET /api/v1/balance
    public function index(Request $request): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->get('_api_key');

        if (! $apiKey->hasPermission('balance.read')) {
            return response()->json(['error' => 'Permission denied.'], 403);
        }

        $balances = $apiKey->merchant->balances()->with('currency')->get();

        $data = $balances
            ->filter(fn (Balance $b) => $b->currency !== null)
            ->map(function (Balance $b) {
                $available = (string) $b->available;
                $locked    = (string) $b->locked;

                return [
                    'currency'  => $b->currency->code,
                    'network'   => $b->currency->network,
                    'available' => $available,
                    'locked'    => $locked,
                    'total'     => bcadd($available !== '' ? $available : '0', $locked !== '' ? $locked : '0', 18),
                ];
            })
            ->values()
            ->all();

        return response()->json(['data' => $data]);
    }
}
