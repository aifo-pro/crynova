@extends('layouts.app')
@section('title', 'Тікети підтримки')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-950">Тікети підтримки</h1>
        <p class="mt-1 text-slate-500">Звернення користувачів. Нові — зверху, з позначкою.</p>
    </div>

    {{-- Quick filters --}}
    <div class="flex flex-wrap gap-2">
        @php
            $chip = fn ($active) => 'inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-bold transition '.($active ? 'border-blue-300 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50');
        @endphp
        <a href="{{ route('admin.support.index') }}" class="{{ $chip(!request()->hasAny(['assignee','status','priority'])) }}">Усі</a>
        <a href="{{ route('admin.support.index', ['assignee'=>'mine']) }}" class="{{ $chip($assignee==='mine') }}">
            Мої @if($counts['mine'])<span class="rounded-full bg-blue-600 px-1.5 text-xs text-white">{{ $counts['mine'] }}</span>@endif
        </a>
        <a href="{{ route('admin.support.index', ['assignee'=>'unassigned']) }}" class="{{ $chip($assignee==='unassigned') }}">
            Непризначені @if($counts['unassigned'])<span class="rounded-full bg-slate-500 px-1.5 text-xs text-white">{{ $counts['unassigned'] }}</span>@endif
        </a>
        <a href="{{ route('admin.support.index', ['status'=>'open']) }}" class="{{ $chip($status==='open') }}">
            Відкриті @if($counts['open'])<span class="rounded-full bg-amber-500 px-1.5 text-xs text-white">{{ $counts['open'] }}</span>@endif
        </a>
    </div>

    @if($departments->isNotEmpty())
        <div class="flex flex-wrap gap-2">
            <span class="self-center text-xs font-bold uppercase tracking-wide text-slate-400">Відділи:</span>
            @foreach($departments as $dept)
                <a href="{{ route('admin.support.index', ['department'=>$dept->id]) }}" class="{{ $chip((int)$deptFilter === $dept->id) }}">{{ $dept->name }}</a>
            @endforeach
        </div>
    @endif

    <form method="GET" class="flex flex-wrap gap-2">
        @if($assignee)<input type="hidden" name="assignee" value="{{ $assignee }}">@endif
        <input name="search" value="{{ request('search') }}" class="fin-input min-w-56 flex-1" placeholder="Пошук за темою, ім'ям, email…">
        <select name="status" class="fin-input w-40">
            <option value="">Усі статуси</option>
            <option value="open" @selected($status==='open')>Відкриті</option>
            <option value="answered" @selected($status==='answered')>Відповіли</option>
            <option value="closed" @selected($status==='closed')>Закриті</option>
        </select>
        <select name="priority" class="fin-input w-40">
            <option value="">Будь-який пріоритет</option>
            <option value="high" @selected($priority==='high')>Високий</option>
            <option value="normal" @selected($priority==='normal')>Звичайний</option>
            <option value="low" @selected($priority==='low')>Низький</option>
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
                        @php $pm = $ticket->priorityMeta(); @endphp
                        @if(($ticket->priority ?: 'normal') !== 'normal')<span class="rounded-full px-2 py-0.5 text-[10px] font-black ring-1 {{ $pm['class'] }}">{{ $pm['label'] }}</span>@endif
                    </p>
                    <p class="truncate text-xs text-slate-400">
                        #{{ $ticket->id }} · {{ optional($ticket->user)->name }} ({{ optional($ticket->user)->email }}) · {{ optional($ticket->last_message_at)->diffForHumans() }}
                        @if($ticket->department)· 🗂 {{ $ticket->department->name }}@endif
                        @if($ticket->assignedAgent)· 👤 {{ $ticket->assignedAgent->name ?: $ticket->assignedAgent->email }}@endif
                    </p>
                </div>
                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold {{ $badge[0] }}">{{ $badge[1] }}</span>
            </a>
        @empty
            <div class="px-5 py-10 text-center text-slate-400">Тікетів немає.</div>
        @endforelse
    </div>

    <div>{{ $tickets->links() }}</div>
</div>
@endsection
