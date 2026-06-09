@extends('layouts.app')
@section('title', __('ui.auth.create_title'))

@section('content')
<section class="flex min-h-[calc(100vh-5rem)] items-center justify-center bg-[#f6f6f7] px-4 py-14">
    <div class="w-full max-w-2xl">
        <div class="rounded-2xl bg-white px-8 py-12 shadow-sm sm:px-12">
            <h1 class="text-center text-2xl font-medium text-slate-950">{{ __('ui.auth.create_title') }}</h1>
            <p class="mt-3 text-center text-sm text-slate-500">{{ __('ui.auth.create_subtitle') }}</p>
            <x-social-auth-buttons class="mt-8" />
            <form method="POST" action="{{ route('register') }}" class="mt-10 grid gap-5 sm:grid-cols-2">
                @csrf
                @if(request('ref'))<input type="hidden" name="ref" value="{{ request('ref') }}">@endif
                <div class="sm:col-span-2">
                    <label class="mb-3 block text-sm text-slate-700">{{ __('ui.auth.name') }}</label>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus class="h-14 w-full rounded-xl border border-slate-200 px-5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10" placeholder="Alex Smith">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-3 block text-sm text-slate-700">{{ __('ui.auth.work_email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="h-14 w-full rounded-xl border border-slate-200 px-5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10" placeholder="finance@example.com">
                </div>
                <div>
                    <label class="mb-3 block text-sm text-slate-700">{{ __('ui.auth.password_placeholder') }}</label>
                    <input type="password" name="password" required class="h-14 w-full rounded-xl border border-slate-200 px-5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10" placeholder="{{ __('ui.auth.password_placeholder') }}">
                </div>
                <div>
                    <label class="mb-3 block text-sm text-slate-700">{{ __('ui.auth.confirm_password') }}</label>
                    <input type="password" name="password_confirmation" required class="h-14 w-full rounded-xl border border-slate-200 px-5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10" placeholder="{{ __('ui.auth.confirm_password') }}">
                </div>
                <label class="flex items-start gap-3 sm:col-span-2">
                    <input type="checkbox" name="agree_terms" value="1" required class="mt-1 rounded border-slate-300 text-blue-600">
                    <span class="text-sm text-slate-500">
                        {{ __('ui.auth.terms') }} <a href="{{ route('legal.terms') }}" class="text-blue-600" target="_blank">{{ __('ui.auth.terms_link') }}</a>
                        {{ __('ui.auth.and') }} <a href="{{ route('legal.privacy') }}" class="text-blue-600" target="_blank">{{ __('ui.auth.privacy_link') }}</a>
                    </span>
                </label>
                <div class="sm:col-span-2">
                    <x-recaptcha-v3 action="register" />
                </div>
                <button type="submit" class="brand-button h-14 rounded-full text-base font-bold sm:col-span-2">{{ __('ui.auth.create_button') }}</button>
                <p class="text-center text-sm text-slate-600 sm:col-span-2">
                    {{ __('ui.auth.have_account') }}
                    <a href="{{ route('login') }}" class="text-blue-600">{{ __('ui.login') }}</a>
                </p>
            </form>
        </div>
    </div>
</section>
@endsection
