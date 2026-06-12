@extends('layouts.app')
@section('title', __('account.settings.notifications_title'))

@section('content')
<div class="space-y-6"
     x-data="{ p: {
        channel_email: {{ $prefs['channel_email'] ? 'true' : 'false' }},
        channel_telegram: {{ $prefs['channel_telegram'] ? 'true' : 'false' }},
        event_auth: {{ $prefs['event_auth'] ? 'true' : 'false' }},
        event_withdraw: {{ $prefs['event_withdraw'] ? 'true' : 'false' }},
        event_partial: {{ $prefs['event_partial'] ? 'true' : 'false' }},
        event_paid: {{ $prefs['event_paid'] ? 'true' : 'false' }},
        event_support: {{ $prefs['event_support'] ? 'true' : 'false' }},
     } }">
    <h1 class="text-2xl font-semibold text-slate-950">{{ __('account.settings.title') }}</h1>
    @include('account.settings._tabs')
    <form method="POST" action="{{ route('account.settings.notifications.update') }}">
        @csrf
        @foreach(['channel_email', 'channel_telegram', 'event_auth', 'event_withdraw', 'event_partial', 'event_paid', 'event_support'] as $k)
            <input type="hidden" name="{{ $k }}" :value="p.{{ $k }} ? 1 : 0">
        @endforeach

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <x-icon name="bell" class="h-5 w-5 text-blue-600" />
                    <h2 class="font-semibold text-slate-950">{{ __('account.settings.notification_channels') }}</h2>
                </div>

                <div class="space-y-4">
                    @foreach(['channel_email' => __('account.settings.to_email'), 'channel_telegram' => 'Telegram'] as $k => $label)
                        <label class="flex items-center justify-between">
                            <span class="text-sm text-slate-700">{{ $label }}</span>
                            <button type="button" @click="p.{{ $k }} = !p.{{ $k }}" role="switch" :class="p.{{ $k }} ? 'bg-blue-600' : 'bg-slate-200'" class="relative inline-flex h-5 w-9 items-center rounded-full transition">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition" :class="p.{{ $k }} ? 'translate-x-4' : 'translate-x-1'"></span>
                            </button>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <x-icon name="bell" class="h-5 w-5 text-blue-600" />
                    <h2 class="font-semibold text-slate-950">{{ __('account.settings.notification_types') }}</h2>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    @foreach(['event_auth' => __('account.settings.event_auth'), 'event_partial' => __('account.settings.event_partial'), 'event_withdraw' => __('account.settings.event_withdraw'), 'event_paid' => __('account.settings.event_paid'), 'event_support' => __('account.settings.event_support')] as $k => $label)
                        <label class="flex items-center gap-2">
                            <button type="button" @click="p.{{ $k }} = !p.{{ $k }}" role="switch" :class="p.{{ $k }} ? 'bg-blue-600' : 'bg-slate-200'" class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition" :class="p.{{ $k }} ? 'translate-x-4' : 'translate-x-1'"></span>
                            </button>
                            <span class="text-sm text-slate-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <x-button type="submit" class="rounded-full px-8">{{ __('account.settings.save_changes') }}</x-button>
        </div>
    </form>
</div>
@endsection
