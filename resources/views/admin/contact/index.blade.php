@extends('layouts.app')
@section('title', 'Звернення')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-950">Звернення підтримки</h1>
        <p class="mt-1 text-slate-500">Вхідні повідомлення з форми зворотного зв'язку.</p>
    </div>

    <form method="GET" class="flex flex-wrap gap-2">
        <input name="search" value="{{ request('search') }}" class="fin-input min-w-56 flex-1" placeholder="Пошук за ім'ям, email, темою…">
        <select name="status" class="fin-input w-44">
            <option value="">Усі статуси</option>
            @foreach(['new'=>'Нове','read'=>'Прочитано','replied'=>'Відповіли','archived'=>'Архів'] as $s=>$lbl)
                <option value="{{ $s }}" @selected(request('status') == $s)>{{ $lbl }}</option>
            @endforeach
        </select>
        <x-button type="submit" variant="secondary">Фільтр</x-button>
    </form>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Ім'я</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Тема</th>
                        <th class="px-5 py-3">Статус</th>
                        <th class="px-5 py-3">Отримано</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($messages as $msg)
                    @php
                        $sb = match($msg->status) {
                            'new'      => ['bg-amber-50 text-amber-600', 'Нове'],
                            'replied'  => ['bg-emerald-50 text-emerald-600', 'Відповіли'],
                            'archived' => ['bg-slate-100 text-slate-500', 'Архів'],
                            default    => ['bg-blue-50 text-blue-600', 'Прочитано'],
                        };
                    @endphp
                    <tr class="transition hover:bg-slate-50 {{ $msg->status === 'new' ? 'bg-amber-50/40' : '' }}">
                        <td class="px-5 py-3.5 font-semibold text-slate-900">{{ $msg->name ?: '—' }}</td>
                        <td class="px-5 py-3.5"><a href="mailto:{{ $msg->email }}" class="text-blue-600 hover:underline">{{ $msg->email }}</a></td>
                        <td class="max-w-xs truncate px-5 py-3.5 text-slate-700">{{ \Illuminate\Support\Str::limit($msg->subject, 50) ?: '—' }}</td>
                        <td class="px-5 py-3.5"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $sb[0] }}">{{ $sb[1] }}</span></td>
                        <td class="px-5 py-3.5 text-xs text-slate-400">{{ $msg->created_at->diffForHumans() }}</td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('admin.contact.show', $msg) }}" class="text-sm font-semibold text-blue-600 hover:underline">Переглянути</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">Повідомлень ще немає.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $messages->links() }}</div>
</div>
@endsection
