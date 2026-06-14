@extends('layouts.app')
@section('title', __('ui.auth.verify_page'))

@section('content')
<section class="flex min-h-[calc(100vh-5rem)] items-center justify-center bg-[#f6f6f7] px-4 py-14">
    <div class="w-full max-w-xl">
        <div class="rounded-2xl bg-white px-8 py-12 text-center shadow-sm sm:px-12">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-blue-50 text-blue-700">
                <x-icon name="message-circle" class="h-6 w-6" />
            </div>
            <h1 class="mt-6 text-2xl font-semibold text-slate-950">{{ __('ui.auth.verify_title') }}</h1>
            <p class="mt-3 text-sm leading-6 text-slate-500">
                {{ __('ui.auth.verify_text') }}
            </p>

            <form method="POST" action="{{ route('verification.send') }}" class="mt-8">
                @csrf
                <x-button type="submit" class="w-full rounded-full py-3">{{ __('ui.auth.verify_resend') }}</x-button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="text-sm font-semibold text-slate-500 hover:text-blue-600">{{ __('ui.auth.logout_account') }}</button>
            </form>
        </div>
    </div>
</section>
@endsection
