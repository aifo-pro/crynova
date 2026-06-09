@extends('layouts.app')
@section('title', 'Відновлення пароля')

@section('content')
<div class="mx-auto flex min-h-[70vh] max-w-md items-center px-4">
    <div class="w-full rounded-[2rem] border border-slate-200 bg-white p-8 shadow-xl shadow-slate-200/70">
        <h1 class="text-center text-2xl font-semibold text-slate-950">Відновлення пароля</h1>
        <p class="mt-2 text-center text-sm leading-6 text-slate-500">Введіть email акаунта, і ми надішлемо посилання для зміни пароля.</p>

        @if(session('success'))
            <x-alert variant="success" class="mt-5">{{ session('success') }}</x-alert>
        @endif

        @if($errors->any())
            <x-alert variant="error" class="mt-5">{{ $errors->first() }}</x-alert>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label class="fin-label">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" required class="fin-input" placeholder="you@example.com">
            </div>
            <x-recaptcha-v3 action="password_reset" />
            <x-button type="submit" class="w-full rounded-full py-3">Надіслати посилання</x-button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            <a href="{{ route('login') }}" class="font-semibold text-blue-600 hover:text-blue-700">Повернутися до входу</a>
        </p>
    </div>
</div>
@endsection
