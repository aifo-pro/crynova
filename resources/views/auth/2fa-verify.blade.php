@extends('layouts.app')
@section('title', __('auth.tfa.verify_heading'))

@section('content')
<section class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-12">
    <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-7 shadow-sm">
        <div class="mb-7 text-center">
            <div class="mx-auto mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-600/25">
                <x-icon name="shield" class="h-6 w-6" />
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-950">{{ __('auth.tfa.verify_heading') }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ __('auth.tfa.verify_subtitle') }}</p>
        </div>
        <form method="POST" action="{{ route('2fa.verify') }}" class="space-y-4">
            @csrf
            <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                   placeholder="123456" maxlength="6" autofocus
                   class="fin-input text-center font-mono text-2xl tracking-[0.4em] @error('code') border-rose-500 @enderror">
            @error('code')<p class="text-center text-xs font-medium text-rose-500">{{ $message }}</p>@enderror
            <x-button type="submit" class="w-full">{{ __('auth.tfa.verify_btn') }}</x-button>
        </form>
    </div>
</section>
@endsection
