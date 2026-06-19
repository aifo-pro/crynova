<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Currency;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WithdrawalController extends Controller
{
    public function __construct(private readonly WithdrawalService $withdrawals) {}

    // GET /api/v1/withdrawals
    public function index(Request $request): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->get('_api_key');

        if (! $apiKey->hasPermission('withdrawals.read')) {
            return response()->json(['error' => 'Permission denied.'], 403);
        }

        $validated = $request->validate([
            'status'   => ['nullable', 'string', Rule::in(['pending', 'approved', 'processing', 'sent', 'confirmed', 'failed', 'cancelled'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $list = $apiKey->merchant->withdrawals()
            ->with('currency')
            ->when($validated['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate((int) ($validated['per_page'] ?? 25));

        return response()->json([
            'data' => $list->getCollection()->map(fn (Withdrawal $w) => $this->payload($w)),
            'meta' => [
                'current_page' => $list->currentPage(),
                'per_page'     => $list->perPage(),
                'total'        => $list->total(),
                'last_page'    => $list->lastPage(),
            ],
        ]);
    }

    // POST /api/v1/withdrawals
    public function store(Request $request): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->get('_api_key');

        if (! $apiKey->hasPermission('withdrawals.create')) {
            return response()->json(['error' => 'Permission denied.'], 403);
        }

        $validated = $request->validate([
            'currency'   => ['required', 'string', 'max:32'],
            'amount'     => ['required', 'numeric', 'gt:0'],
            'to_address' => ['required', 'string', 'max:100'],
            'memo'       => ['nullable', 'string', 'max:100'],
        ]);

        $currency = Currency::where('code', strtoupper($validated['currency']))->where('is_active', true)->first();
        if (! $currency) {
            return response()->json(['error' => 'Unknown or inactive currency.'], 422);
        }

        try {
            $withdrawal = $this->withdrawals->request(
                $apiKey->merchant,
                $currency,
                (string) $validated['amount'],
                $validated['to_address'],
                $validated['memo'] ?? null,
            );
        } catch (\RuntimeException) {
            return response()->json(['error' => 'Insufficient balance.'], 422);
        }

        // Created as "pending" — released only after manual approval in the admin panel.
        return response()->json($this->payload($withdrawal->load('currency')), 201);
    }

    // GET /api/v1/withdrawals/{uuid}
    public function show(Request $request, string $uuid): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->get('_api_key');

        if (! $apiKey->hasPermission('withdrawals.read')) {
            return response()->json(['error' => 'Permission denied.'], 403);
        }

        $withdrawal = $apiKey->merchant->withdrawals()->with('currency')->where('uuid', $uuid)->firstOrFail();

        return response()->json($this->payload($withdrawal));
    }

    private function payload(Withdrawal $w): array
    {
        return [
            'withdrawal_id' => $w->uuid,
            'status'        => $w->status,
            'currency'      => optional($w->currency)->code,
            'amount'        => (string) $w->amount,
            'fee'           => $w->fee !== null ? (string) $w->fee : null,
            'amount_sent'   => $w->amount_sent !== null ? (string) $w->amount_sent : null,
            'to_address'    => $w->to_address,
            'memo'          => $w->memo,
            'tx_hash'       => $w->tx_hash,
            'created_at'    => $w->created_at?->toIso8601String(),
            'approved_at'   => $w->approved_at?->toIso8601String(),
        ];
    }
}
