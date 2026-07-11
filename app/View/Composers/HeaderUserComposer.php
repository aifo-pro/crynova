<?php

namespace App\View\Composers;

use App\Models\Balance;
use Illuminate\View\View;

class HeaderUserComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $displayName = trim($user->name) !== '' ? $user->name : strtok($user->email, '@');
        $initial     = strtoupper(mb_substr($displayName, 0, 1));

        if ($user->isAdmin()) {
            $subtitle    = __('ui.administrator');
            $showBalance = false;
            $balanceUsd  = null;
        } else {
            $merchantIds = $user->merchants()->pluck('id');
            $balanceUsd  = $merchantIds->isEmpty()
                ? 0.0
                : (float) Balance::whereIn('merchant_id', $merchantIds)->sum('available');

            $showBalance = $balanceUsd > 0;
            $subtitle    = $showBalance
                ? '$ ' . number_format($balanceUsd, 2)
                : __('ui.account_label');
        }

        $view->with('headerUser', [
            'name'        => $displayName,
            'email'       => $user->email,
            'initial'     => $initial,
            'subtitle'    => $subtitle,
            'showBalance' => $showBalance,
            'balanceUsd'  => $balanceUsd,
            'isAdmin'     => $user->isAdmin(),
            'canAdmin'    => $user->isAdmin() || $user->isSupport(),
            'readonly'    => $user->isSupport(),
        ]);
    }
}
