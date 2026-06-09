@extends('layouts.app')
@section('title', '2FA verification')

@section('content')
<section class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-12">
    <x-card class="w-full max-w-md">
        <div class="mb-7 text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-lg border border-teal-400/30 bg-teal-400/10 text-teal-200">
                <x-icon name="shield" class="h-6 w-6" />
            </div>
            <h1 class="text-2xl font-semibold text-white">Two-factor verification</h1>
            <p class="mt-2 text-sm text-slate-400">Enter the 6-digit code from your authenticator app.</p>
        </div>
        <form method="POST" action="{{ route('2fa.verify') }}" class="space-y-4">
            @csrf
            <input type="text" name="code" placeholder="123456" maxlength="6" autofocus class="fin-input text-center font-mono text-2xl">
            <x-button type="submit" class="w-full">Verify</x-button>
        </form>
    </x-card>
</section>
@endsection
