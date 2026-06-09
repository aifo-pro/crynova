<?php

namespace App\Http\Middleware;

use App\Models\Merchant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/*
 * Ensures the {merchant} route parameter belongs to the authenticated user.
 * Admins may access any merchant. Shares $merchant + lifecycle flags with views.
 */
class EnsureMerchantOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user     = $request->user();
        $merchant = $request->route('merchant');

        // Resolve to a model if route binding gave us an id
        if (! $merchant instanceof Merchant) {
            $merchant = Merchant::findOrFail($merchant);
            $request->route()->setParameter('merchant', $merchant);
        }

        abort_unless($user, 403);

        // Owner or admin only
        if ($merchant->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403, 'You do not have access to this merchant.');
        }

        // Make the current merchant available everywhere (controllers + Blade views)
        app()->instance('currentMerchant', $merchant);
        view()->share('currentMerchant', $merchant);

        // Auto-fill the {merchant} route parameter so existing route('merchant.*')
        // calls in views resolve to this merchant without an explicit argument.
        URL::defaults(['merchant' => $merchant->id]);

        return $next($request);
    }
}
