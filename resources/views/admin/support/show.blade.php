@extends('layouts.app')
@section('title', 'Тікет #'.$ticket->id)

@section('content')
@php $pm = $ticket->priorityMeta(); @endphp
<div class="space-y-4">
    <a href="{{ route('admin.support.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-900">
        <x-icon name="arrow-left" class="h-4 w-4" /> До тікетів
    </a>

    <div class="grid gap-5 xl:grid-cols-[1fr_20rem] xl:items-start">
        <div class="min-w-0">
            @include('partials.support-chat', ['ticket' => $ticket, 'isAdmin' => true])
        </div>

        {{-- Agent side panel --}}
        <aside class="space-y-4">
            {{-- Assignment & priority --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-black uppercase tracking-[0.12em] text-slate-950">Керування</h3>

                <div class="mt-4">
                    <p class="text-xs font-bold text-slate-400">Пріоритет</p>
                    <div class="mt-2 flex gap-2">
                        @foreach(['low'=>'Низький','normal'=>'Звичайний','high'=>'Високий'] as $val=>$lbl)
                            <form method="POST" action="{{ route('admin.support.priority', $ticket) }}" class="flex-1">
                                @csrf
                                <input type="hidden" name="priority" value="{{ $val }}">
                                <button type="submit" class="w-full rounded-xl border px-2 py-1.5 text-xs font-bold transition {{ ($ticket->priority ?: 'normal') === $val ? 'border-blue-300 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-500 hover:bg-slate-50' }}">{{ $lbl }}</button>
                            </form>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-xs font-bold text-slate-400">Виконавець</p>
                    <form method="POST" action="{{ route('admin.support.assign', $ticket) }}" class="mt-2 flex gap-2">
                        @csrf
                        <select name="assigned_to" class="fin-input h-10 flex-1 text-sm" onchange="this.form.submit()">
                            <option value="">— Не призначено —</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" @selected($ticket->assigned_to === $agent->id)>{{ $agent->name ?: $agent->email }}</option>
                            @endforeach
                        </select>
                    </form>
                    @if($ticket->assigned_to !== auth()->id())
                        <form method="POST" action="{{ route('admin.support.assign', $ticket) }}" class="mt-2">
                            @csrf
                            <input type="hidden" name="assigned_to" value="{{ auth()->id() }}">
                            <button type="submit" class="w-full rounded-xl bg-blue-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-blue-700">Взяти на себе</button>
                        </form>
                    @endif
                </div>

                <dl class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-xs">
                    <div class="flex justify-between"><dt class="text-slate-400">Статус</dt><dd class="font-bold text-slate-700">{{ $ticket->status }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-400">Пріоритет</dt><dd><span class="rounded-full px-2 py-0.5 text-[11px] font-black ring-1 {{ $pm['class'] }}">{{ $pm['label'] }}</span></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-400">Створено</dt><dd class="font-bold text-slate-700">{{ $ticket->created_at?->format('d.m.Y') }}</dd></div>
                </dl>
            </div>

            {{-- Internal notes --}}
            <div class="rounded-3xl border border-amber-200 bg-amber-50/40 p-5 shadow-sm">
                <h3 class="flex items-center gap-2 text-sm font-black uppercase tracking-[0.12em] text-amber-800">
                    <x-icon name="lock" class="h-4 w-4" /> Внутрішні нотатки
                </h3>
                <p class="mt-1 text-xs text-amber-700/80">Видно лише команді. Клієнт їх не бачить.</p>

                <div class="mt-3 space-y-2">
                    @forelse($ticket->internalNotes->sortByDesc('created_at') as $note)
                        <div class="rounded-xl border border-amber-100 bg-white p-3">
                            <p class="whitespace-pre-line text-sm text-slate-700">{{ $note->body }}</p>
                            <p class="mt-1 text-[11px] text-slate-400">{{ $note->author?->name ?? 'Агент' }} · {{ $note->created_at->format('d.m.Y H:i') }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">Нотаток ще немає.</p>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('admin.support.note', $ticket) }}" class="mt-3 space-y-2">
                    @csrf
                    <textarea name="body" rows="2" class="fin-input text-sm" placeholder="Додати нотатку…" required></textarea>
                    <x-button type="submit" variant="secondary" class="w-full text-sm">Додати нотатку</x-button>
                </form>
            </div>
        </aside>
    </div>
</div>
@endsection
