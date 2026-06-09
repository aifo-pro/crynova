@extends('layouts.app')
@section('title', 'Звернення')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.contact.index') }}" class="text-slate-400 hover:text-white"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-white">Повідомлення</h1>
    </div>
    <div class="grid gap-6 lg:grid-cols-[1fr_0.5fr]">
        <x-card title="{{ $message->subject }}">
            <div class="space-y-3 text-sm">
                <div class="flex gap-4">
                    <span class="w-24 text-slate-500">Від</span>
                    <span class="text-white">{{ $message->name }} &lt;{{ $message->email }}&gt;</span>
                </div>
                <div class="flex gap-4">
                    <span class="w-24 text-slate-500">Отримано</span>
                    <span class="text-slate-300">{{ $message->created_at->format('d M Y, H:i') }}</span>
                </div>
                <div class="flex gap-4">
                    <span class="w-24 text-slate-500">IP</span>
                    <span class="font-mono text-xs text-slate-400">{{ $message->ip ?? '—' }}</span>
                </div>
            </div>
            <div class="mt-5 rounded-lg border border-slate-800 bg-slate-900/60 p-4">
                <p class="whitespace-pre-wrap text-sm text-slate-200">{{ $message->message }}</p>
            </div>
        </x-card>

        <x-card title="Керування">
            <form method="POST" action="{{ route('admin.contact.update', $message) }}" class="space-y-4">
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
                    <label class="fin-label">Нотатки адміна <span class="text-slate-500">(внутрішні)</span></label>
                    <textarea name="admin_notes" rows="5" class="fin-input text-sm"
                              placeholder="Внутрішні нотатки, напр. відповідь надіслано на email…">{{ $message->admin_notes }}</textarea>
                </div>

                <x-button type="submit" icon="save">Зберегти</x-button>
            </form>
        </x-card>
    </div>
</div>
@endsection
