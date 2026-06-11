<?php

namespace App\Services;

use App\Models\PaymentInvoice;
use App\Models\MerchantWebhook;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public function dispatch(PaymentInvoice $invoice, string $event): void
    {
        $merchant = $invoice->merchant;
        $configuredWebhook = MerchantWebhook::where('merchant_id', $merchant->id)
            ->where('is_active', true)
            ->latest()
            ->first();

        if ($configuredWebhook && $configuredWebhook->events && ! in_array($event, $configuredWebhook->events, true)) {
            return;
        }

        $url = $configuredWebhook?->url ?? $merchant->webhook_url;
        $secret = $configuredWebhook?->secret ?? $merchant->webhook_secret;

        if (! $url) {
            return;
        }

        $payload = $this->buildPayload($invoice, $event);
        $attempt = $invoice->webhook_attempts + 1;

        $log = WebhookLog::create([
            'invoice_id'  => $invoice->id,
            'merchant_id' => $merchant->id,
            'event'       => $event,
            'url'         => $url,
            'payload'     => $payload,
            'attempt'     => $attempt,
        ]);

        $this->send($log, $url, $payload, $secret);

        if ($configuredWebhook) {
            $configuredWebhook->update(['last_triggered_at' => now()]);
        }
    }

    public function retry(WebhookLog $log): void
    {
        $merchant = $log->merchant;
        $configuredWebhook = MerchantWebhook::where('merchant_id', $merchant->id)
            ->where('is_active', true)
            ->where('url', $log->url)
            ->latest()
            ->first();

        $this->send($log, $log->url, $log->payload, $configuredWebhook?->secret ?? $merchant->webhook_secret, incrementAttempt: true);
    }

    private function send(WebhookLog $log, string $url, array $payload, ?string $secret, bool $incrementAttempt = false): void
    {
        if ($incrementAttempt) {
            $log->update(['attempt' => $log->attempt + 1]);
            $log->refresh();
        }

        $json      = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $signature = $secret ? 'sha256=' . hash_hmac('sha256', $json, $secret) : '';

        try {
            $response = Http::timeout((int) config('crynova.webhook_timeout', 10))
                ->withHeaders([
                    'Content-Type'       => 'application/json',
                    'X-Crynova-Event'    => $payload['event'],
                    'X-Crynova-Sig'      => $signature,
                    'X-Crynova-Delivery' => $log->id,
                ])
                ->withBody($json, 'application/json')
                ->post($url);

            $success = $response->successful();

            $log->update([
                'http_status'   => $response->status(),
                'response_body' => substr($response->body(), 0, 2000),
                'success'       => $success,
                'next_retry_at' => $success ? null : $this->nextRetryAt($log->attempt),
            ]);

            if ($success) {
                $log->invoice->update([
                    'webhook_attempts'     => $log->attempt,
                    'webhook_last_sent_at' => now(),
                    'webhook_delivered'    => true,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("Webhook delivery failed for invoice {$log->invoice_id}: " . $e->getMessage());

            $log->update([
                'success'       => false,
                'response_body' => $e->getMessage(),
                'next_retry_at' => $this->nextRetryAt($log->attempt),
            ]);
        }
    }

    private function buildPayload(PaymentInvoice $invoice, string $event): array
    {
        return [
            'event'          => $event,
            'invoice_id'     => $invoice->uuid,
            'order_id'       => $invoice->order_id,
            'status'         => $invoice->status,
            'price_amount'   => $invoice->price_amount !== null ? (string) $invoice->price_amount : null,
            'price_currency' => $invoice->price_currency,
            'pay_currency'   => optional($invoice->currency)->code,
            'currency'       => optional($invoice->currency)->code,
            'amount'         => $invoice->amount !== null ? (string) $invoice->amount : null,
            'received'       => (string) $invoice->amount_received,
            'address'        => $invoice->pay_address,
            'paid_at'        => $invoice->paid_at?->toIso8601String(),
            'metadata'       => $invoice->metadata,
            'created_at'     => $invoice->created_at->toIso8601String(),
        ];
    }

    // Exponential backoff: 5m, 30m, 2h, 8h, 24h
    private function nextRetryAt(int $attempt): \Carbon\Carbon
    {
        $minutes = match ($attempt) {
            1 => 5,
            2 => 30,
            3 => 120,
            4 => 480,
            default => 1440,
        };

        return now()->addMinutes($minutes);
    }

    /** Send a synthetic test payload to the merchant webhook URL. */
    public function sendTest(\App\Models\Merchant $merchant): array
    {
        $url = $merchant->webhook_url ?: $merchant->callback_url;

        if (! $url) {
            return ['success' => false, 'message' => 'Webhook URL не налаштовано (webhook_url / callback_url).'];
        }

        $secret = $merchant->webhook_secret;
        $payload = [
            'event'      => 'test.ping',
            'invoice_id' => 'test-' . \Illuminate\Support\Str::uuid()->toString(),
            'order_id'   => 'admin-test',
            'status'     => 'test',
            'currency'   => $merchant->base_currency_code ?? 'USD',
            'amount'     => '0.01',
            'received'   => '0',
            'address'    => null,
            'paid_at'    => null,
            'metadata'   => ['source' => 'admin_test'],
            'created_at' => now()->toIso8601String(),
        ];

        $json      = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $signature = $secret ? 'sha256=' . hash_hmac('sha256', $json, $secret) : '';

        try {
            $response = Http::timeout((int) config('crynova.webhook_timeout', 10))
                ->withHeaders([
                    'Content-Type'       => 'application/json',
                    'X-Crynova-Event'    => $payload['event'],
                    'X-Crynova-Sig'      => $signature,
                    'X-Crynova-Delivery' => 'test',
                ])
                ->withBody($json, 'application/json')
                ->post($url);

            return [
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? "Тест надіслано (HTTP {$response->status()})."
                    : "Помилка доставки (HTTP {$response->status()}): " . substr($response->body(), 0, 200),
                'http_status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Помилка: ' . $e->getMessage()];
        }
    }
}
