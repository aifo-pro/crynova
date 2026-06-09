@extends('layouts.app')
@section('title', $merchant->name)

@section('content')
@php $statusMeta = $merchant->statusMeta(); @endphp
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-semibold text-slate-950">{{ $merchant->name }}</h1>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-{{ $statusMeta['color'] }}-50 px-3 py-1 text-xs font-semibold text-{{ $statusMeta['color'] }}-600">
                    <span class="h-1.5 w-1.5 rounded-full bg-{{ $statusMeta['color'] }}-500"></span>{{ $statusMeta['label'] }}
                </span>
            </div>
            <p class="mt-1 text-slate-500">
                {{ $merchant->merchant_type === 'telegram' ? '@'.$merchant->telegram_channel : $merchant->domain }}
            </p>
        </div>
    </div>

    @if($merchant->isUnverified())
        <div class="rounded-3xl border border-blue-200 bg-blue-50 p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white"><x-icon name="shield-check" class="h-5 w-5" /></span>
                    <div>
                        <p class="font-semibold text-blue-900">{{ __('merchant.control.step_verify') }}</p>
                        <p class="mt-0.5 text-sm text-blue-700">{{ __('merchant.control.confirm_control', ['target' => $merchant->merchant_type === 'telegram' ? __('merchant.control.telegram_channel') : __('merchant.control.domain')]) }}</p>
                    </div>
                </div>
                <x-button href="{{ route('merchant.verification', $merchant) }}" icon="shield-check">{{ __('merchant.control.verify_now') }}</x-button>
            </div>
        </div>
    @elseif($merchant->isOnModeration())
        <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-500 text-white"><x-icon name="clock" class="h-5 w-5" /></span>
                <div>
                    <p class="font-semibold text-amber-900">{{ __('merchant.control.on_moderation') }}</p>
                    <p class="mt-0.5 text-sm text-amber-700">{{ __('merchant.control.on_moderation_text') }}</p>
                </div>
            </div>
        </div>
    @elseif($merchant->isRejected())
        <div class="rounded-3xl border border-rose-200 bg-rose-50 p-6">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-rose-500 text-white"><x-icon name="alert-triangle" class="h-5 w-5" /></span>
                <div class="flex-1">
                    <p class="font-semibold text-rose-900">{{ __('merchant.control.rejected') }}</p>
                    @if($merchant->reject_reason)
                        <p class="mt-1 text-sm text-rose-700"><strong>{{ __('merchant.control.reason') }}:</strong> {{ $merchant->reject_reason }}</p>
                    @endif
                    <form method="POST" action="{{ route('merchant.resubmit', $merchant) }}" class="mt-3">
                        @csrf
                        <x-button type="submit" variant="secondary" icon="arrow-trend-up">{{ __('merchant.control.resubmit') }}</x-button>
                    </form>
                </div>
            </div>
        </div>
    @elseif($merchant->isBlocked())
        <div class="rounded-3xl border border-rose-200 bg-rose-50 p-6">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-rose-600 text-white"><x-icon name="shield-off" class="h-5 w-5" /></span>
                <div>
                    <p class="font-semibold text-rose-900">{{ __('merchant.control.blocked') }}</p>
                    <p class="mt-0.5 text-sm text-rose-700">{{ __('merchant.control.blocked_text') }}</p>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white"><x-icon name="check" class="h-5 w-5" /></span>
                    <div>
                        <p class="font-semibold text-emerald-900">{{ __('merchant.control.active') }}</p>
                        <p class="mt-0.5 text-sm text-emerald-700">{{ __('merchant.control.active_text') }}</p>
                    </div>
                </div>
                <x-button href="{{ route('merchant.dashboard', $merchant) }}" icon="gauge">{{ __('merchant.control.open_dashboard') }}</x-button>
            </div>
        </div>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="mb-4 font-semibold text-slate-950">{{ __('merchant.control.details') }}</p>
        <dl class="grid gap-x-8 gap-y-4 sm:grid-cols-2">
            <div><dt class="text-xs uppercase tracking-wide text-slate-400">{{ __('merchant.control.id') }}</dt><dd class="mt-0.5 font-mono text-slate-800">#{{ $merchant->id }}</dd></div>
            <div><dt class="text-xs uppercase tracking-wide text-slate-400">{{ __('merchant.control.type') }}</dt><dd class="mt-0.5 text-slate-800">{{ ucfirst($merchant->merchant_type) }}</dd></div>
            <div><dt class="text-xs uppercase tracking-wide text-slate-400">{{ $merchant->merchant_type === 'telegram' ? __('merchant.control.channel') : __('merchant.control.domain') }}</dt><dd class="mt-0.5 text-slate-800">{{ $merchant->merchant_type === 'telegram' ? '@'.$merchant->telegram_channel : $merchant->domain }}</dd></div>
            <div><dt class="text-xs uppercase tracking-wide text-slate-400">{{ __('merchant.control.base_currency') }}</dt><dd class="mt-0.5 text-slate-800">{{ $merchant->base_currency_code }}</dd></div>
            <div><dt class="text-xs uppercase tracking-wide text-slate-400">{{ __('merchant.control.business_type') }}</dt><dd class="mt-0.5 text-slate-800">{{ $merchant->business_type ?? '-' }}</dd></div>
            <div><dt class="text-xs uppercase tracking-wide text-slate-400">{{ __('merchant.control.fee') }}</dt><dd class="mt-0.5 text-slate-800">{{ $merchant->fee_percent }}%</dd></div>
            <div><dt class="text-xs uppercase tracking-wide text-slate-400">{{ __('merchant.control.created') }}</dt><dd class="mt-0.5 text-slate-800">{{ $merchant->created_at->format('d M Y') }}</dd></div>
            <div><dt class="text-xs uppercase tracking-wide text-slate-400">{{ __('merchant.control.verified') }}</dt><dd class="mt-0.5 text-slate-800">{{ $merchant->verified_at?->format('d M Y') ?? __('merchant.control.not_verified') }}</dd></div>
        </dl>
    </div>
</div>
@endsection
