@extends('layouts.app')
@section('title', 'Звернення')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.contact.index') }}" class="text-slate-400 hover:text-slate-900"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-slate-950">Звернення</h1>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_0.5fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <h2 class="text-lg font-black text-slate-950">{{ $message->subject ?: 'Без теми' }}</h2>
                @php
                    $sb = match($message->status) {
                        'new'      => ['bg-amber-50 text-amber-600', 'Нове'],
                        'replied'  => ['bg-emerald-50 text-emerald-600', 'Відповіли'],
                        'archived' => ['bg-slate-100 text-slate-500', 'Архів'],
                        default    => ['bg-blue-50 text-blue-600', 'Прочитано'],
                    };
                @endphp
                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold {{ $sb[0] }}">{{ $sb[1] }}</span>
            </div>

            <dl class="mt-5 space-y-2.5 text-sm">
                <div class="flex gap-4">
                    <dt class="w-28 shrink-0 text-slate-400">Від</dt>
                    <dd class="font-semibold text-slate-900">{{ $message->name }} <span class="font-normal text-slate-500">&lt;{{ $message->email }}&gt;</span></dd>
                </div>
                <div class="flex gap-4">
                    <dt class="w-28 shrink-0 text-slate-400">Отримано</dt>
                    <dd class="text-slate-600">{{ $message->created_at->format('d.m.Y H:i') }}</dd>
                </div>
                <div class="flex gap-4">
                    <dt class="w-28 shrink-0 text-slate-400">IP</dt>
                    <dd class="font-mono text-xs text-slate-500">{{ $message->ip ?? '—' }}</dd>
                </div>
            </dl>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="whitespace-pre-wrap break-words text-sm leading-6 text-slate-800">{{ $message->message }}</p>
            </div>

            <div class="mt-5">
                <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.($message->subject ?: 'Звернення')) }}" class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    <x-icon name="message-circle" class="h-4 w-4" /> Відповісти на email
                </a>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">Керування</h2>
            <form method="POST" action="{{ route('admin.contact.update', $message) }}" class="mt-4 space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="fin-label">Статус</label>
                    <select name="status" class="fin-input">
                        @foreach(['new'=>'Нове','read'=>'Прочитано','replied'=>'Відповіли','archived'=>'Архів'] as $s=>$lbl)
                            <option value="{{ $s }}" @selected($message->status === $s)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fin-label">Нотатки адміна <span class="text-slate-400">(внутрішні)</span></label>
                    <textarea name="admin_notes" rows="5" class="fin-input text-sm"
                              placeholder="Внутрішні нотатки, напр. відповідь надіслано на email…">{{ $message->admin_notes }}</textarea>
                </div>
                <x-button type="submit" icon="save" class="w-full">Зберегти</x-button>
            </form>
        </div>
    </div>
</div>
@endsection
