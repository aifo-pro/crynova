<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateInvoiceRequest;
use App\Models\ApiIdempotencyKey;
use App\Models\ApiKey;
use App\Models\PaymentInvoice;
use App\Services\InvoiceService;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly WebhookService $webhookService,
    ) {}

    // GET /api/v1/invoices
    public function index(Request $request): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->get('_api_key');

        if (! $apiKey->hasPermission('invoices.read')) {
            return response()->json(['error' => 'Permission denied.'], 403);
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(['pending', 'waiting_confirmations', 'paid', 'underpaid', 'overpaid', 'expired', 'failed', 'refunded'])],
            'order_id' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:32'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $invoices = $apiKey->merchant->invoices()
            ->with('currency', 'transactions')
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['order_id'] ?? null, fn ($query, $orderId) => $query->where('order_id', $orderId))
            ->when($validated['currency'] ?? null, fn ($query, $currency) =>
                $query->whereHas('currency', fn ($currencyQuery) => $currencyQuery->where('code', $currency))
            )
            ->latest()
            ->paginate((int) ($validated['per_page'] ?? 25));

        return response()->json([
            'data' => $invoices->getCollection()->map(fn (PaymentInvoice $invoice) => $this->invoicePayload($invoice)),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
                'last_page' => $invoices->lastPage(),
            ],
        ]);
    }

    // POST /api/v1/invoices
    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->get('_api_key');

        if (! $apiKey->hasPermission('invoices.create')) {
            return response()->json(['error' => 'Permission denied.'], 403);
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        if ($idempotencyKey) {
            $requestHash = hash('sha256', json_encode($request->validated()));

            $existing = ApiIdempotencyKey::where('merchant_id', $apiKey->merchant_id)
                ->where('idempotency_key', $idempotencyKey)
                ->where('expires_at', '>', now())
                ->first();

            if ($existing) {
                if ($existing->request_hash !== $requestHash) {
                    return response()->json(['error' => 'Idempotency key reused with different payload.'], 422);
                }

                return response()->json($existing->response_body, $existing->http_status);
            }
        }

        $invoice = $this->invoiceService->create($apiKey->merchant, $request->validated());

        $response = $this->invoicePayload($invoice);

        if ($idempotencyKey) {
            ApiIdempotencyKey::create([
                'merchant_id'     => $apiKey->merchant_id,
                'idempotency_key' => $idempotencyKey,
                'request_hash'    => hash('sha256', json_encode($request->validated())),
                'http_status'     => 201,
                'response_body'   => $response,
                'expires_at'      => now()->addHours(24),
            ]);
        }

        return response()->json($response, 201);
    }

    // GET /api/v1/invoices/{uuid}
    public function show(Request $request, string $uuid): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->get('_api_key');

        if (! $apiKey->hasPermission('invoices.read')) {
            return response()->json(['error' => 'Permission denied.'], 403);
        }

        $invoice = $apiKey->merchant->invoices()
            ->with('currency', 'transactions')
            ->where('uuid', $uuid)
            ->firstOrFail();

        return response()->json($this->invoicePayload($invoice));
    }

    // GET /api/v1/invoices/{uuid}/status
    public function status(Request $request, string $uuid): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->get('_api_key');

        if (! $apiKey->hasPermission('invoices.read')) {
            return response()->json(['error' => 'Permission denied.'], 403);
        }

        $invoice = $apiKey->merchant->invoices()
            ->with('currency', 'transactions')
            ->where('uuid', $uuid)
            ->firstOrFail();

        return response()->json([
            'invoice_id' => $invoice->uuid,
            'order_id' => $invoice->order_id,
            'status' => $invoice->status,
            'is_final' => $invoice->isFinal(),
            'amount' => $invoice->amount !== null ? (string) $invoice->amount : null,
            'amount_received' => (string) $invoice->amount_received,
            'currency' => optional($invoice->currency)->code,
            'confirmations' => $invoice->transactions->max('confirmations') ?? 0,
            'confirmations_required' => optional($invoice->currency)->confirmations_required,
            'paid_at' => $invoice->paid_at?->toIso8601String(),
            'expires_at' => $invoice->expires_at?->toIso8601String(),
        ]);
    }

    // POST /api/v1/invoices/{uuid}/cancel
    public function cancel(Request $request, string $uuid): JsonResponse
    {
        /** @var ApiKey $apiKey */
        $apiKey = $request->get('_api_key');

        if (! $apiKey->hasPermission('invoices.cancel')) {
            return response()->json(['error' => 'Permission denied.'], 403);
        }

        $invoice = $apiKey->merchant->invoices()
            ->with('currency', 'transactions')
            ->where('uuid', $uuid)
            ->firstOrFail();

        if (! in_array($invoice->status, ['pending', 'waiting_confirmations'], true)) {
            return response()->json(['error' => 'Only pending invoices can be canceled.'], 422);
        }

        if ($invoice->transactions()->exists() || (float) $invoice->amount_received > 0) {
            return response()->json(['error' => 'Invoice already has payment activity and cannot be canceled.'], 422);
        }

        $invoice->update(['status' => 'expired', 'expires_at' => now()]);
        $this->webhookService->dispatch($invoice->refresh()->load('currency', 'merchant'), 'invoice.expired');

        return response()->json($this->invoicePayload($invoice->refresh()->load('currency', 'transactions')));
    }

    private function invoicePayload(PaymentInvoice $invoice): array
    {
        return [
            'invoice_id' => $invoice->uuid,
            'order_id' => $invoice->order_id,
            'status' => $invoice->status,
            // Original price (may be fiat, e.g. UAH/USD).
            'price_amount' => $invoice->price_amount !== null ? (string) $invoice->price_amount : null,
            'price_currency' => $invoice->price_currency,
            // Crypto chosen for payment (null until the customer picks one for a fiat invoice).
            'pay_currency' => optional($invoice->currency)->code,
            'currency' => optional($invoice->currency)->code,
            'amount' => $invoice->amount !== null ? (string) $invoice->amount : null,
            'amount_received' => (string) $invoice->amount_received,
            'pay_address' => $invoice->pay_address,
            'pay_memo' => $invoice->pay_memo,
            'description' => $invoice->description,
            'metadata' => $invoice->metadata,
            'expires_at' => $invoice->expires_at?->toIso8601String(),
            'paid_at' => $invoice->paid_at?->toIso8601String(),
            'net_amount' => $invoice->net_amount !== null ? (string) $invoice->net_amount : null,
            'checkout_url' => route('checkout.show', $invoice->uuid),
            'transactions' => $invoice->transactions->map(fn ($tx) => [
                'tx_hash' => $tx->tx_hash,
                'amount' => (string) $tx->amount,
                'confirmations' => $tx->confirmations,
                'status' => $tx->status,
            ])->values()->all(),
        ];
    }
}
