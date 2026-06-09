@extends('layouts.app')
@section('title', 'Новий користувач')

@section('content')
<div class="mx-auto max-w-xl space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.users.index') }}" class="text-slate-400 hover:text-white"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-white">Новий користувач</h1>
    </div>
    <x-card>
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
            @csrf
            <div><label class="fin-label">Ім'я</label><input name="name" class="fin-input" value="{{ old('name') }}" required></div>
            <div><label class="fin-label">Email</label><input name="email" type="email" class="fin-input" value="{{ old('email') }}" required></div>
            <div><label class="fin-label">Роль</label>
                <select name="role" class="fin-input">
                    <option value="merchant">Мерчант</option>
                    <option value="support">Підтримка</option>
                    <option value="admin">Адміністратор</option>
                </select>
            </div>
            <div><label class="fin-label">Пароль</label><input name="password" type="password" class="fin-input" required></div>
            <div><label class="fin-label">Підтвердження пароля</label><input name="password_confirmation" type="password" class="fin-input" required></div>
            <x-button type="submit" icon="plus">Створити</x-button>
        </form>
    </x-card>
</div>
@endsection
