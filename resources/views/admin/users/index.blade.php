@extends('layouts.app')
@section('title', 'Користувачі')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <x-badge variant="blue">Адмін-панель</x-badge>
            <h1 class="mt-3 text-3xl font-semibold text-slate-950">Користувачі</h1>
            <p class="mt-1 text-slate-500">Управління акаунтами, ролями, паролями та доступом.</p>
        </div>
        <x-button href="{{ route('admin.users.create') }}" icon="plus">Новий користувач</x-button>
    </div>
    <x-card>
        <form method="GET" class="grid gap-3 md:grid-cols-3">
            <input name="search" value="{{ request('search') }}" class="fin-input md:col-span-2" placeholder="Пошук за ім'ям або email...">
            <select name="role" class="fin-input">
                <option value="">Роль: усі</option>
                <option value="admin" @selected(request('role') === 'admin')>Адміністратор</option>
                <option value="merchant" @selected(request('role') === 'merchant')>Мерчант</option>
                <option value="support" @selected(request('role') === 'support')>Підтримка</option>
            </select>
            <x-button type="submit" variant="secondary">Фільтр</x-button>
        </form>
    </x-card>

    <x-card>
        <x-table :headers="['Користувач', 'Роль', 'Касс', '2FA', 'Статус', 'Реєстрація', '']">
            @forelse($users as $user)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 text-sm font-bold text-white">{{ strtoupper(substr($user->email,0,1)) }}</span>
                            <div class="min-w-0">
                                <p class="truncate font-medium text-slate-950">{{ $user->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <x-badge :variant="$user->role === 'admin' ? 'blue' : ($user->role === 'support' ? 'yellow' : 'teal')">{{ $user->role }}</x-badge>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-600">{{ $user->merchants()->count() }}</td>
                    <td class="px-4 py-3">
                        @if($user->google2fa_enabled)<span class="text-xs font-semibold text-emerald-600">Увімкнено</span>@else<span class="text-xs text-slate-400">—</span>@endif
                    </td>
                    <td class="px-4 py-3">
                        @if($user->trashed())<x-badge variant="red">Видалено</x-badge>
                        @elseif(!$user->is_active)<x-badge variant="red">Заблоковано</x-badge>
                        @else<x-badge variant="green">Активний</x-badge>@endif
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $user->created_at->format('d.m.Y') }}</td>
                    <td class="px-4 py-3 text-right">
                        @if($user->trashed())
                            <form method="POST" action="{{ route('admin.users.restore', $user->id) }}">@csrf<button class="text-sm font-semibold text-blue-600 hover:underline">Відновити</button></form>
                        @else
                            <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                                <x-icon name="settings" class="h-3.5 w-3.5" /> Деталі
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">Користувачів не знайдено.</td></tr>
            @endforelse
        </x-table>
        <div class="mt-4">{{ $users->links() }}</div>
    </x-card>
</div>
@endsection
