<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/*
 * Affiliate / partner program dashboard.
 */
class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $referrals = $user->referrals()->latest()->get();
        $referralLink = route('register', ['ref' => $user->referral_code]);

        // Commission model: a share of platform fees from referred merchants.
        // Earnings tracking is wired here; payout settlement is a later layer.
        $stats = [
            'referrals'      => $referrals->count(),
            'active'         => $referrals->filter(fn ($u) => $u->merchants()->where('status', 'active')->exists())->count(),
            'commission_pct' => 20, // % of platform fee shared with the partner
            'earned'         => 0.00,
        ];

        return view('account.partner', compact('user', 'referrals', 'referralLink', 'stats'));
    }
}
