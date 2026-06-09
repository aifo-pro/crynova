@extends('layouts.app')
@section('title', 'Журнал аудиту')

@section('content')
<div class="space-y-6">
    <div>
        <x-badge variant="blue">Адмін-панель</x-badge>
        <h1 class="mt-3 text-3xl font-semibold text-white">Журнал аудиту</h1>
        <p class="mt-1 text-slate-400">Потік подій безпеки та операцій. Лише додавання записів.</p>
    </div>

    <x-card>
        <form method="GET" class="grid gap-3 md:grid-cols-3">
            <input name="action" value="{{ request('action') }}" class="fin-input md:col-span-2" placeholder="Фільтр за дією (напр. invoice.paid, auth.login)…">
            <x-button type="submit" variant="secondary">Фільтр</x-button>
        </form>
    </x-card>

    <x-card>
        <x-table :headers="['Час', 'Актор', 'Дія', 'Обʼєкт', 'IP']">
            @forelse($logs as $log)
            <tr class="hover:bg-slate-900/60">
                <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                    {{ $log->created_at->format('Y-m-d H:i:s') }}
                </td>
                <td class="px-4 py-3 text-sm">
                    @if($log->user)
                        <p class="text-slate-300">{{ $log->user->name }}</p>
                        <p class="text-xs text-slate-500">{{ $log->actor_type }}</p>
                    @else
                        <span class="text-xs text-slate-500">{{ $log->actor_type }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 font-mono text-xs text-teal-200">{{ $log->action }}</td>
                <td class="px-4 py-3 text-xs text-slate-400">
                    @if($log->subject_type)
                        {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                    @else
                        —
                    @endif
                </td>
                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $log->actor_ip ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Записів аудиту немає.</td></tr>
            @endforelse
        </x-table>
        <div class="mt-4">{{ $logs->links() }}</div>
    </x-card>
</div>
@endsection
