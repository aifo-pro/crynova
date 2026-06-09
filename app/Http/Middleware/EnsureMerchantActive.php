<?php

namespace App\Http\Middleware;

use App\Models\Merchant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
 * Feature lock: blocks access to merchant feature pages (invoices, payouts,
 * widget, settlement, etc.) until the merchant has been approved (status=active).
 * Unverified / on-moderation / rejected / blocked merchants are redirected to
 * the merchant control page where the appropriate next-step CTA is shown.
 */
class EnsureMerchantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $merchant = $request->route('merchant');

        if (! $merchant instanceof Merchant) {
            $merchant = Merchant::findOrFail($merchant);
        }

        if (! $merchant->featuresUnlocked()) {
            return redirect()
                ->route('merchant.control', $merchant)
                ->with('warning', 'This section unlocks once your merchant is approved and active.');
        }

        return $next($request);
    }
}
