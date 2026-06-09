@extends('layouts.app')
@section('title', __('account.settings.team_title'))

@section('content')
<div class="space-y-6" x-data="{ showInvite: false }">
    <h1 class="text-2xl font-semibold text-slate-950">{{ __('account.settings.title') }}</h1>
    @include('account.settings._tabs')
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2">
                <x-icon name="user" class="h-5 w-5 text-blue-600" />
                <h2 class="font-semibold text-slate-950">{{ __('account.settings.access_sharing') }}</h2>
            </div>

            <div class="space-y-3 text-sm text-slate-500">
                <p>{{ __('account.settings.access_text_1') }}</p>
                <p>{{ __('account.settings.access_text_2') }}</p>
                <p>{{ __('account.settings.access_text_3') }}</p>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="font-semibold text-slate-950">{{ __('account.settings.team') }}</h2>
                <button type="button" @click="showInvite = !showInvite" class="inline-flex items-center gap-1.5 rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    <x-icon name="plus" class="h-4 w-4" />
                    {{ __('account.settings.add_user') }}
                </button>
            </div>

            <div x-show="showInvite" x-cloak class="mb-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <form method="POST" action="{{ route('account.settings.team.invite') }}" class="space-y-3">
                    @csrf

                    <div>
                        <label class="fin-label">Email</label>
                        <input name="email" type="email" required class="fin-input" placeholder="user@example.com">
                    </div>

                    <div>
                        <label class="fin-label">{{ __('account.settings.role') }}</label>
                        <select name="role" class="fin-input">
                            <option value="viewer">{{ __('account.settings.role_viewer') }}</option>
                            <option value="manager">{{ __('account.settings.role_manager') }}</option>
                            <option value="admin">{{ __('account.settings.role_admin') }}</option>
                        </select>
                    </div>

                    <x-button type="submit" icon="plus">{{ __('account.settings.invite') }}</x-button>
                </form>
            </div>

            @if($members->isEmpty())
                <div class="py-10 text-center">
                    <p class="text-slate-400">{{ __('account.settings.no_users') }}</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($members as $m)
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-slate-950">{{ $m->email }}</p>
                                <p class="text-xs text-slate-400">{{ ['viewer' => __('account.settings.role_viewer'), 'manager' => __('account.settings.role_manager'), 'admin' => __('account.settings.role_admin')][$m->role] ?? $m->role }} · {{ $m->status }}</p>
                            </div>

                            <form method="POST" action="{{ route('account.settings.team.remove', $m) }}" onsubmit="return confirm('{{ __('account.settings.revoke_access') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-rose-500 hover:underline">{{ __('account.settings.delete') }}</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
