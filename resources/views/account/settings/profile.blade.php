@extends('layouts.app')
@section('title', __('account.settings.profile_title'))

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-semibold text-slate-950">
        {{ __('account.settings.title') }}
        <x-help-tip :text="__('account.settings.help')" />
    </h1>

    @include('account.settings._tabs')
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-2">
                <x-icon name="user" class="h-5 w-5 text-blue-600" />
                <h2 class="font-semibold text-slate-950">{{ __('account.settings.user_data') }}</h2>
            </div>

            <form method="POST" action="{{ route('account.settings.profile.update') }}" class="space-y-5">
                @csrf
                @method('PATCH')
                <input type="hidden" name="name" value="{{ $user->name }}">

                <div>
                    <label class="fin-label">{{ __('account.settings.email') }}</label>
                    <div class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-[10px] font-bold text-blue-700">{{ strtoupper(substr($user->email, 0, 1)) }}</span>
                        <span class="text-sm text-slate-700">{{ $user->email }}</span>
                    </div>
                </div>

                <div>
                    <label class="fin-label">Telegram</label>
                    <div class="flex items-center rounded-xl border border-slate-200 focus-within:border-blue-400">
                        <span class="px-3 text-sm text-slate-400">@</span>
                        <input name="telegram" type="text" class="flex-1 border-0 bg-transparent py-2.5 pr-3 text-sm focus:outline-none focus:ring-0" value="{{ $user->telegram }}" placeholder="username / 123456789">
                    </div>
                    <p class="mt-1.5 text-xs leading-5 text-slate-400">{!! __('account.settings.telegram_hint', ['info' => 'https://t.me/userinfobot']) !!}</p>
                </div>

                <div>
                    <label class="fin-label">{{ __('account.settings.language') }}</label>
                    <select name="language" class="fin-input">
                        <option value="uk" @selected(in_array($user->language, ['uk', 'ua'], true))>Українська</option>
                        <option value="en" @selected($user->language === 'en')>English</option>
                        <option value="pl" @selected($user->language === 'pl')>Polski</option>
                        <option value="ru" @selected($user->language === 'ru')>Русский</option>
                    </select>
                </div>

                <x-button type="submit" icon="save">{{ __('account.settings.save') }}</x-button>
            </form>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-2">
                <x-icon name="lock" class="h-5 w-5 text-blue-600" />
                <h2 class="font-semibold text-slate-950">{{ __('account.settings.password_change') }}</h2>
            </div>

            <form method="POST" action="{{ route('account.settings.password') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="fin-label">{{ __('account.settings.current_password') }}</label>
                    <input name="current_password" type="password" autocomplete="current-password" class="fin-input @error('current_password') border-rose-500 @enderror" required>
                    @error('current_password')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="fin-label">{{ __('account.settings.new_password') }}</label>
                    <input name="password" type="password" autocomplete="new-password" class="fin-input @error('password') border-rose-500 @enderror" required>
                    @error('password')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="fin-label">{{ __('account.settings.confirm_password') }}</label>
                    <input name="password_confirmation" type="password" autocomplete="new-password" class="fin-input" required>
                </div>

                <x-button type="submit" class="w-full rounded-full">{{ __('account.settings.change_password') }}</x-button>
            </form>
        </div>
    </div>
</div>
@endsection
