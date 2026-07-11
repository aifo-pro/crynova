@extends('layouts.app')
@section('title', $department ? 'Редагування відділу' : 'Новий відділ')

@section('content')
@php $memberIds = $department ? $department->agents->pluck('id')->all() : []; @endphp
<div class="mx-auto w-full max-w-3xl space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.support-departments.index') }}" class="text-slate-400 hover:text-blue-600"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-slate-950">{{ $department ? 'Редагування відділу' : 'Новий відділ' }}</h1>
    </div>

    <form method="POST" action="{{ $department ? route('admin.support-departments.update', $department) : route('admin.support-departments.store') }}" class="space-y-6">
        @csrf
        @if($department)@method('PATCH')@endif

        <x-card>
            <div class="space-y-4">
                <div>
                    <label class="fin-label">Назва відділу</label>
                    <input name="name" type="text" class="fin-input" value="{{ old('name', $department->name ?? '') }}" placeholder="Напр. Платежі" required>
                </div>
                <div>
                    <label class="fin-label">Опис</label>
                    <input name="description" type="text" class="fin-input" value="{{ old('description', $department->description ?? '') }}" placeholder="Короткий опис напряму">
                </div>
                <div class="flex items-center gap-6">
                    <div>
                        <label class="fin-label">Порядок</label>
                        <input name="sort" type="number" min="0" class="fin-input w-28" value="{{ old('sort', $department->sort ?? 0) }}">
                    </div>
                    <label class="mt-6 flex cursor-pointer items-center gap-3">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $department->is_active ?? true)) class="rounded border-slate-300 text-blue-600">
                        <span class="text-sm text-slate-700">Активний</span>
                    </label>
                </div>
            </div>
        </x-card>

        <x-card title="Агенти відділу" subtitle="Хто отримує тікети цього напряму">
            <div class="grid gap-2 sm:grid-cols-2">
                @forelse($agents as $agent)
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5 transition hover:bg-slate-50">
                        <input type="checkbox" name="agents[]" value="{{ $agent->id }}" @checked(in_array($agent->id, old('agents', $memberIds))) class="rounded border-slate-300 text-blue-600">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-bold text-slate-800">{{ $agent->name ?: $agent->email }}</span>
                            <span class="block truncate text-xs text-slate-400">{{ $agent->email }} · {{ $agent->role === 'admin' ? 'адмін' : 'підтримка' }}</span>
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-slate-400">Немає агентів. Призначте користувачам роль «Техпідтримка».</p>
                @endforelse
            </div>
        </x-card>

        <div class="flex justify-end">
            <x-button type="submit" icon="save">Зберегти відділ</x-button>
        </div>
    </form>
</div>
@endsection
