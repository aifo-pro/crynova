@extends('layouts.app')
@section('title', __('ui.auth.disabled_page'))

@section('content')
<section class="flex min-h-[calc(100vh-5rem)] items-center justify-center bg-[#f6f6f7] px-4 py-14">
    <div class="w-full max-w-xl">
        <div class="rounded-2xl bg-white px-8 py-12 text-center shadow-sm sm:px-12">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-blue-50 text-blue-700">
                <x-icon name="shield" class="h-6 w-6" />
            </div>
            <h1 class="mt-6 text-2xl font-semibold text-slate-950">{{ __('ui.auth.disabled_title') }}</h1>
            <p class="mt-3 text-sm leading-6 text-slate-500">
                {{ __('ui.auth.disabled_text') }}
            </p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <x-button href="{{ route('login') }}" class="rounded-full px-8">{{ __('ui.auth.disabled_login') }}</x-button>
                <x-button href="{{ route('contact') }}" variant="secondary" class="rounded-full px-8">{{ __('ui.auth.disabled_contact') }}</x-button>
            </div>
        </div>
    </div>
</section>
@endsection
