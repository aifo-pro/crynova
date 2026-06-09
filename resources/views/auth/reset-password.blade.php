@extends('layouts.app')
@section('title', 'Новий пароль')

@section('content')
<div class="mx-auto flex min-h-[70vh] max-w-md items-center px-4">
    <div class="w-full rounded-[2rem] border border-slate-200 bg-white p-8 shadow-xl shadow-slate-200/70">
        <h1 class="text-center text-2xl font-semibold text-slate-950">Створіть новий пароль</h1>
        <p class="mt-2 text-center text-sm leading-6 text-slate-500">Пароль має містити великі й малі літери та цифри.</p>

        @if($errors->any())
            <x-alert variant="error" class="mt-5">{{ $errors->first() }}</x-alert>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label class="fin-label">Email</label>
                <input name="email" type="email" value="{{ old('email', $email) }}" required class="fin-input">
            </div>
            <div>
                <label class="fin-label">Новий пароль</label>
                <input name="password" type="password" required class="fin-input" autocomplete="new-password">
            </div>
            <div>
                <label class="fin-label">Повторіть пароль</label>
                <input name="password_confirmation" type="password" required class="fin-input" autocomplete="new-password">
            </div>
            <x-button type="submit" class="w-full rounded-full py-3">Оновити пароль</x-button>
        </form>
    </div>
</div>
@endsection
