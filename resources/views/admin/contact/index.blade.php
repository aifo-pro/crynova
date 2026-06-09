@extends('layouts.app')
@section('title', 'Звернення')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-white">Звернення підтримки</h1>
        <p class="mt-1 text-slate-400">Вхідні повідомлення з форми зворотного зв'язку.</p>
    </div>
    <x-card>
        <form method="GET" class="flex flex-wrap gap-3">
            <input name="search" value="{{ request('search') }}" class="fin-input flex-1 min-w-48" placeholder="Пошук за ім'ям, email, темою…">
            <select name="status" class="fin-input w-40">
                <option value="">Усі статуси</option>
                @foreach(['new'=>'Нове','read'=>'Прочитано','replied'=>'Відповіли','archived'=>'Архів'] as $s=>$lbl)
                    <option value="{{ $s }}" @selected(request('status') == $s)>{{ $lbl }}</option>
                @endforeach
            </select>
            <x-button type="submit" variant="secondary">Фільтр</x-button>
        </form>
    </x-card>

    <x-card>
        <x-table :headers="['Ім\'я', 'Email', 'Тема', 'Статус', 'Отримано', '']">
            @forelse($messages as $msg)
            <tr class="hover:bg-slate-900/60 {{ $msg->status === 'new' ? 'border-l-2 border-teal-400' : '' }}">
                <td class="px-4 py-3 font-medium text-white">{{ $msg->name }}</td>
                <td class="px-4 py-3 text-sm text-slate-400">{{ $msg->email }}</td>
                <td class="px-4 py-3 text-sm text-slate-300">{{ Str::limit($msg->subject, 50) }}</td>
                <td class="px-4 py-3">
                    @php $sc = ['new'=>'text-teal-300','read'=>'text-slate-300','replied'=>'text-blue-300','archived'=>'text-slate-500']; @endphp
                    <span class="text-xs font-semibold {{ $sc[$msg->status] ?? '' }}">{{ ucfirst($msg->status) }}</span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ $msg->created_at->diffForHumans() }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.contact.show', $msg) }}" class="text-sm text-teal-300 hover:text-white">Переглянути</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Повідомлень ще немає.</td></tr>
            @endforelse
        </x-table>
        <div class="mt-4">{{ $messages->links() }}</div>
    </x-card>
</div>
@endsection
