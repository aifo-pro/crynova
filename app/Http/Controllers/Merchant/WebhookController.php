<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Merchant;
use App\Models\MerchantWebhook;
use App\Models\WebhookLog;
use App\Rules\PublicHttpUrl;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(Request $request, Merchant $merchant)
    {
        $webhook  = MerchantWebhook::where('merchant_id', $merchant->id)
            ->where('is_active', true)
            ->first();

        $logs = WebhookLog::where('merchant_id', $merchant->id)
            ->with('invoice')
            ->latest()
            ->paginate(20);

        $availableEvents = [
            'invoice.created',
            'invoice.waiting_confirmations',
            'invoice.paid',
            'invoice.underpaid',
            'invoice.overpaid',
            'invoice.expired',
            'invoice.failed',
            'invoice.refunded',
        ];

        return view('merchant.webhooks.index', compact('merchant', 'webhook', 'logs', 'availableEvents'));
    }

    public function save(Request $request, Merchant $merchant)
    {
        $request->validate([
            'url'    => ['required', 'url', 'max:500', new PublicHttpUrl],
            'events' => ['nullable', 'array'],
        ]);

        $existing = MerchantWebhook::where('merchant_id', $merchant->id)->where('is_active', true)->first();

        if ($existing) {
            $existing->update([
                'url'    => $request->input('url'),
                'events' => $request->input('events', []),
            ]);
            $rawSecret = null;
        } else {
            ['model' => $webhook, 'raw_secret' => $rawSecret] = MerchantWebhook::createForMerchant(
                $merchant,
                $request->input('url'),
                $request->input('events', []),
            );
        }

        AuditLog::record('webhook.saved', $existing ?? $webhook ?? null);

        $response = redirect()->route('merchant.webhooks.index', $merchant)
            ->with('success', __('merchant.webhooks.saved'));

        if ($rawSecret) {
            $response = $response->with('new_webhook_secret', $rawSecret);
        }

        return $response;
    }

    public function regenerateSecret(Request $request, Merchant $merchant)
    {
        $webhook  = MerchantWebhook::where('merchant_id', $merchant->id)->where('is_active', true)->firstOrFail();

        $rawSecret = \Illuminate\Support\Str::random(32);
        $webhook->update([
            'secret_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString($rawSecret),
        ]);

        AuditLog::record('webhook.secret_regenerated', $webhook);

        return redirect()->route('merchant.webhooks.index', $merchant)
            ->with('new_webhook_secret', $rawSecret)
            ->with('success', __('merchant.webhooks.secret_regenerated'));
    }
}
