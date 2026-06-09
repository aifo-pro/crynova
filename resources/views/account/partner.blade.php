@extends('layouts.app')
@section('title', __('account.partner.title'))

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-950">{{ __('account.partner.title') }}</h1>
        <p class="mt-1 text-slate-500">{{ __('account.partner.text', ['percent' => $stats['commission_pct']]) }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-400">{{ __('account.partner.invited') }}</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['referrals'] }}</p></div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-400">{{ __('account.partner.active') }}</p><p class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['active'] }}</p></div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-400">{{ __('account.partner.rate') }}</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['commission_pct'] }}%</p></div>
        <div class="rounded-3xl bg-blue-600 p-5 text-white shadow-lg shadow-blue-600/20"><p class="text-sm text-blue-100">{{ __('account.partner.earned') }}</p><p class="mt-2 text-3xl font-bold">$ {{ number_format($stats['earned'], 2) }}</p></div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-3 font-semibold text-slate-950">{{ __('account.partner.link') }}</h2>
        <div class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5">
            <span id="ref-link" class="flex-1 truncate text-sm text-blue-600">{{ $referralLink }}</span>
            <button type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50" data-copy-target="ref-link">{{ __('account.partner.copy') }}</button>
        </div>
        <p class="mt-2 text-xs text-slate-400">{{ __('account.partner.code') }} <span class="font-mono font-semibold text-slate-600">{{ $user->referral_code }}</span></p>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 font-semibold text-slate-950">{{ __('account.partner.users') }}</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                    <th class="px-3 py-3">{{ __('account.partner.user') }}</th><th class="px-3 py-3">{{ __('account.partner.projects') }}</th><th class="px-3 py-3">{{ __('account.partner.registered') }}</th>
                </tr></thead>
                <tbody>
                    @forelse($referrals as $r)
                        <tr class="border-b border-slate-50">
                            <td class="px-3 py-3 text-slate-700">{{ \Illuminate\Support\Str::mask($r->email, '*', 3, 4) }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $r->merchants()->count() }}</td>
                            <td class="px-3 py-3 text-xs text-slate-400">{{ $r->created_at->format('d.m.Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-10 text-center text-slate-400">{{ __('account.partner.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
