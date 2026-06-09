<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Merchant;
use Illuminate\Http\Request;

/*
 * Merchant "general" / control page — always accessible to the owner,
 * regardless of lifecycle status. Shows status, next-step CTA and basic info.
 */
class ControlController extends Controller
{
    public function index(Request $request, Merchant $merchant)
    {
        return view('merchant.control', compact('merchant'));
    }

    /** Toggle the project's sandbox / test mode. */
    public function toggleTestMode(Request $request, Merchant $merchant)
    {
        $merchant->update(['test_mode' => ! $merchant->test_mode]);
        AuditLog::record($merchant->test_mode ? 'merchant.test_mode_on' : 'merchant.test_mode_off', $merchant);

        return back()->with('success', $merchant->test_mode ? __('merchant.test_mode_enabled') : __('merchant.test_mode_disabled'));
    }

    /** Re-submit a rejected merchant back to moderation. */
    public function resubmit(Request $request, Merchant $merchant)
    {
        abort_unless($merchant->isRejected(), 422);

        $merchant->update([
            'status'        => Merchant::STATUS_MODERATION,
            'reject_reason' => null,
        ]);

        AuditLog::record('merchant.resubmitted', $merchant);

        return back()->with('success', __('merchant.resubmitted'));
    }
}
