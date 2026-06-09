@extends('layouts.app')
@section('title', __('ui.login'))

@section('content')
<section class="flex min-h-[calc(100vh-5rem)] items-center justify-center bg-[#f6f6f7] px-4 py-14">
    <div class="w-full max-w-xl">
        <div class="rounded-2xl bg-white px-8 py-12 shadow-sm sm:px-12">
            <h1 class="text-center text-2xl font-medium text-slate-950">{{ __('ui.auth.sign_in_title') }}</h1>

            <form method="POST" action="{{ route('login') }}" class="mt-10 space-y-6">
                @csrf
                <div>
                    <label class="mb-3 block text-sm text-slate-700">{{ __('ui.auth.email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus class="h-14 w-full rounded-xl border border-slate-200 px-5 text-slate-950 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10" placeholder="E-mail">
                </div>
                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <label class="text-sm text-slate-700">{{ __('ui.auth.password') }}</label>
                        <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-700">{{ __('ui.auth.forgot') }}</a>
                    </div>
                    <input type="password" name="password" required class="h-14 w-full rounded-xl border border-slate-200 px-5 text-slate-950 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10" placeholder="{{ __('ui.auth.password_placeholder') }}">
                </div>
                <x-recaptcha-v3 action="login" />
                <button type="submit" class="brand-button h-14 w-full rounded-full text-base font-bold transition hover:brightness-105">{{ __('ui.auth.login_button') }}</button>
            </form>

            <x-social-auth-buttons class="mt-8" />
            <p class="mt-8 text-center text-sm text-slate-600">
                {{ __('ui.auth.no_account') }}
                @if((bool) \App\Models\Setting::get('registration_enabled', true))
                    <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-700">{{ __('ui.sign_up') }}</a>
                @endif
            </p>
        </div>
    </div>
</section>
@endsection
