@extends('layouts.app')
@section('title', 'Шаблони відповідей')

@section('content')
<div class="mx-auto w-full max-w-5xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                <x-icon name="book" class="h-3.5 w-3.5" /> База відповідей
            </div>
            <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950">Шаблони відповідей</h1>
            <p class="mt-2 text-sm text-slate-500">Готові відповіді різними мовами для швидких відповідей у тікетах.</p>
        </div>
        <x-button href="{{ route('admin.templates.create') }}" icon="plus" class="rounded-2xl">Новий шаблон</x-button>
    </div>

    <form method="GET" class="grid gap-3 rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_14rem_auto]">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input name="search" value="{{ request('search') }}" class="fin-input h-12 rounded-2xl pl-11" placeholder="Пошук за назвою або текстом…">
        </div>
        <select name="category" class="fin-input h-12 rounded-2xl">
            <option value="">Усі категорії</option>
            @foreach($categories as $cat)
                <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
            @endforeach
        </select>
        <x-button type="submit" class="h-12 rounded-2xl px-7">Фільтр</x-button>
    </form>

    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse($templates as $tpl)
                <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-black text-slate-950">{{ $tpl->title }}</p>
                            @if($tpl->category)<span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-600">{{ $tpl->category }}</span>@endif
                            @unless($tpl->is_active)<span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-400">вимкнено</span>@endunless
                        </div>
                        <p class="mt-1 line-clamp-2 max-w-2xl text-sm text-slate-500">{{ \Illuminate\Support\Str::limit(strip_tags($tpl->body), 160) }}</p>
                        <div class="mt-2 flex gap-1.5 text-[10px] font-bold uppercase text-slate-400">
                            <span class="rounded bg-slate-100 px-1.5 py-0.5">UA</span>
                            @if($tpl->body_en)<span class="rounded bg-slate-100 px-1.5 py-0.5">EN</span>@endif
                            @if($tpl->body_pl)<span class="rounded bg-slate-100 px-1.5 py-0.5">PL</span>@endif
                            @if($tpl->body_ru)<span class="rounded bg-slate-100 px-1.5 py-0.5">RU</span>@endif
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <form method="POST" action="{{ route('admin.templates.toggle', $tpl) }}">
                            @csrf
                            <button type="submit" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-blue-200 hover:text-blue-700">
                                {{ $tpl->is_active ? 'Вимкнути' : 'Увімкнути' }}
                            </button>
                        </form>
                        <a href="{{ route('admin.templates.edit', $tpl) }}" class="rounded-xl bg-blue-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-blue-700">Редагувати</a>
                        <form method="POST" action="{{ route('admin.templates.destroy', $tpl) }}" onsubmit="return confirm('Видалити шаблон?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="grid h-9 w-9 place-items-center rounded-xl border border-rose-200 text-rose-500 transition hover:bg-rose-50"><x-icon name="trash" class="h-4 w-4" /></button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-500"><x-icon name="book" class="h-6 w-6" /></div>
                    <p class="mt-4 text-base font-black text-slate-950">Шаблонів ще немає</p>
                    <p class="mt-1 text-sm text-slate-500">Створіть перший шаблон відповіді.</p>
                </div>
            @endforelse
        </div>
    </div>

    @if($templates->hasPages())
        <div>{{ $templates->links() }}</div>
    @endif
</div>
@endsection
