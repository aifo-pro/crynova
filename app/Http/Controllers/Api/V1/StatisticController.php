<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticController extends Controller
{
    // GET /api/v1/statistics
    public function index(Request $request): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->get('_api_key');

        if (! $apiKey->hasPermission('statistics.read')) {
            return response()->json(['error' => 'Permission denied.'], 403);
        }

        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
            'currency'  => ['nullable', 'string', 'max:32'],
        ]);

        $base = $apiKey->merchant->invoices()
            ->when($validated['date_from'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when($validated['date_to'] ?? null, fn ($q, $d) => $q->where('created_at', '<=', $d))
            ->when($validated['currency'] ?? null, fn ($q, $c) =>
                $q->whereHas('currency', fn ($cq) => $cq->where('code', $c))
            );

        $total   = (clone $base)->count();
        $paid    = (clone $base)->where('status', 'paid')->count();
        $expired = (clone $base)->whereIn('status', ['expired', 'failed'])->count();
        $pending = (clone $base)->whereIn('status', ['pending', 'waiting_confirmations'])->count();

        // Turnover of paid invoices, grouped by the invoice price currency.
        $volume = (clone $base)->where('status', 'paid')
            ->selectRaw('price_currency, SUM(price_amount) as amount')
            ->groupBy('price_currency')
            ->get()
            ->filter(fn ($r) => $r->price_currency !== null)
            ->map(fn ($r) => ['currency' => $r->price_currency, 'amount' => (string) $r->amount])
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'invoices_total'   => $total,
                'invoices_paid'    => $paid,
                'invoices_pending' => $pending,
                'invoices_expired' => $expired,
                'conversion'       => $total > 0 ? round($paid / $total * 100, 2) : 0,
                'paid_volume'      => $volume,
                'period'           => [
                    'from' => $validated['date_from'] ?? null,
                    'to'   => $validated['date_to'] ?? null,
                ],
            ],
        ]);
    }
}
