@extends('layouts.app')
@section('title', 'Тікети підтримки')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-950">Тікети підтримки</h1>
        <p class="mt-1 text-slate-500">Звернення користувачів. Нові — зверху, з позначкою.</p>
    </div>

    <form method="GET" class="flex flex-wrap gap-2">
        <input name="search" value="{{ request('search') }}" class="fin-input min-w-56 flex-1" placeholder="Пошук за темою, ім'ям, email…">
        <select name="status" class="fin-input w-44">
            <option value="">Усі статуси</option>
            <option value="open" @selected($status==='open')>Відкриті</option>
            <option value="answered" @selected($status==='answered')>Відповіли</option>
            <option value="closed" @selected($status==='closed')>Закриті</option>
        </select>
        <x-button type="submit" variant="secondary">Фільтр</x-button>
    </form>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        @forelse($tickets as $ticket)
            @php
                $badge = match($ticket->status) {
                    'closed'   => ['bg-slate-100 text-slate-500', 'Закрито'],
                    'answered' => ['bg-emerald-50 text-emerald-600', 'Відповіли'],
                    default    => ['bg-amber-50 text-amber-600', 'Відкрито'],
                };
            @endphp
            <a href="{{ route('admin.support.show', $ticket) }}" class="flex items-center gap-4 border-b border-slate-100 px-5 py-4 transition last:border-0 hover:bg-slate-50 {{ $ticket->admin_unread ? 'bg-blue-50/40' : '' }}">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl {{ $ticket->admin_unread ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500' }}"><x-icon name="message-circle" class="h-5 w-5" /></span>
                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-2 truncate font-semibold text-slate-950">
                        {{ $ticket->subject }}
                        @if($ticket->admin_unread)<span class="rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-bold text-white">NEW</span>@endif
                    </p>
                    <p class="truncate text-xs text-slate-400">#{{ $ticket->id }} · {{ optional($ticket->user)->name }} ({{ optional($ticket->user)->email }}) · {{ optional($ticket->last_message_at)->diffForHumans() }}</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $badge[0] }}">{{ $badge[1] }}</span>
            </a>
        @empty
            <div class="px-5 py-10 text-center text-slate-400">Тікетів немає.</div>
        @endforelse
    </div>

    <div>{{ $tickets->links() }}</div>
</div>
@endsection
