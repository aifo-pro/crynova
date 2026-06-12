@extends('layouts.app')
@section('title', 'Журнал аудиту')

@php
    $actionColor = function (string $action) {
        return match (true) {
            str_contains($action, 'deleted'), str_contains($action, 'blocked'), str_contains($action, 'rejected'), str_contains($action, 'failed') => 'bg-rose-50 text-rose-600',
            str_contains($action, 'created'), str_contains($action, 'approved'), str_contains($action, 'paid'), str_contains($action, 'enabled') => 'bg-emerald-50 text-emerald-600',
            str_contains($action, 'login'), str_contains($action, '2fa'), str_contains($action, 'auth') => 'bg-blue-50 text-blue-600',
            str_contains($action, 'updated'), str_contains($action, 'saved'), str_contains($action, 'regenerated') => 'bg-amber-50 text-amber-600',
            default => 'bg-slate-100 text-slate-600',
        };
    };
@endphp

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-950">Журнал аудиту</h1>
        <p class="mt-1 text-slate-500">Потік подій безпеки та операцій. Лише додавання записів.</p>
    </div>

    <form method="GET" class="flex flex-wrap gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="relative flex-1 min-w-56">
            <input name="action" value="{{ request('action') }}" class="fin-input pl-9" placeholder="Фільтр за дією (напр. invoice.paid, auth.login)…">
            <x-icon name="globe" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-300" />
        </div>
        <x-button type="submit" variant="secondary">Фільтр</x-button>
        @if(request('action'))
            <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center rounded-xl px-3 py-2 text-sm font-semibold text-slate-400 hover:text-slate-700">Скинути</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3.5">Час</th>
                        <th class="px-4 py-3.5">Актор</th>
                        <th class="px-4 py-3.5">Дія</th>
                        <th class="px-4 py-3.5">Обʼєкт</th>
                        <th class="px-4 py-3.5">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                    <tr class="transition hover:bg-slate-50/60">
                        <td class="whitespace-nowrap px-5 py-3.5 text-xs text-slate-500">
                            <p class="font-medium text-slate-700">{{ $log->created_at->format('d.m.Y') }}</p>
                            <p class="text-slate-400">{{ $log->created_at->format('H:i:s') }}</p>
                        </td>
                        <td class="px-4 py-3.5">
                            @if($log->user)
                                <p class="text-sm font-medium text-slate-900">{{ $log->user->name }}</p>
                                <p class="text-xs text-slate-400">{{ $log->actor_type }}</p>
                            @else
                                <span class="text-xs text-slate-400">{{ $log->actor_type ?: '—' }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 font-mono text-xs font-semibold {{ $actionColor($log->action) }}">{{ $log->action }}</span>
                        </td>
                        <td class="px-4 py-3.5 text-xs text-slate-500">
                            @if($log->subject_type)
                                <span class="font-medium text-slate-700">{{ class_basename($log->subject_type) }}</span> <span class="text-slate-400">#{{ $log->subject_id }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3.5 font-mono text-xs text-slate-400">{{ $log->actor_ip ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-14 text-center text-slate-400">Записів аудиту немає.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $logs->links() }}</div>@endif
    </div>
</div>
@endsection
