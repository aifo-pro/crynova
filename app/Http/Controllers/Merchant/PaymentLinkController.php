<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\PaymentLink;
use Illuminate\Http\Request;

class PaymentLinkController extends Controller
{
    public function index(Request $request, Merchant $merchant)
    {
        $links = $merchant->paymentLinks()
            ->with('currency')
            ->latest()
            ->paginate(20);

        $currencies = Currency::where('is_active', true)->orderBy('code')->get();

        return view('merchant.payment-links.index', compact('links', 'currencies'));
    }

    public function store(Request $request, Merchant $merchant)
    {
        $validated = $request->validate([
            'title'           => ['nullable', 'string', 'max:100'],
            'description'     => ['nullable', 'string', 'max:500'],
            'amount'          => ['nullable', 'numeric', 'gte:0'],
            'currency_id'     => ['nullable', 'integer', 'exists:currencies,id'],
            'max_uses'        => ['nullable', 'integer', 'min:1'],
            'success_url'     => ['nullable', 'url', 'max:500'],
            'order_id_prefix' => ['nullable', 'string', 'max:50'],
        ]);

        // amount=0 means variable, treat as null
        if (isset($validated['amount']) && (float) $validated['amount'] <= 0) {
            $validated['amount'] = null;
        }

        $link = $merchant->paymentLinks()->create($validated);

        AuditLog::record('payment_link.created', $link);

        return back()->with('success', "Link created. Share: {$link->getPublicUrl()}");
    }

    public function toggle(Request $request, Merchant $merchant, PaymentLink $link)
    {
        abort_unless($link->merchant_id === $merchant->id, 403);

        $link->update(['is_active' => ! $link->is_active]);
        AuditLog::record($link->is_active ? 'payment_link.activated' : 'payment_link.deactivated', $link);

        return back()->with('success', 'Link ' . ($link->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function destroy(Request $request, Merchant $merchant, PaymentLink $link)
    {
        abort_unless($link->merchant_id === $merchant->id, 403);

        AuditLog::record('payment_link.deleted', $link);
        $link->delete();

        return back()->with('success', 'Payment link deleted.');
    }
}
