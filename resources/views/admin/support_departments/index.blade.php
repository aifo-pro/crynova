@extends('layouts.app')
@section('title', 'Відділи підтримки')

@section('content')
<div class="mx-auto w-full max-w-4xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                <x-icon name="layers" class="h-3.5 w-3.5" /> Спеціалізації
            </div>
            <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950">Відділи підтримки</h1>
            <p class="mt-2 text-sm text-slate-500">Розподіл агентів за напрямами. Тікети можна передавати між відділами.</p>
        </div>
        <x-button href="{{ route('admin.support-departments.create') }}" icon="plus" class="rounded-2xl">Новий відділ</x-button>
    </div>

    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse($departments as $dept)
                <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-black text-slate-950">{{ $dept->name }}</p>
                            @unless($dept->is_active)<span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-400">вимкнено</span>@endunless
                        </div>
                        @if($dept->description)<p class="mt-1 text-sm text-slate-500">{{ $dept->description }}</p>@endif
                        <p class="mt-2 text-xs font-semibold text-slate-400">{{ $dept->agents_count }} агентів · {{ $dept->tickets_count }} тікетів</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('admin.support-departments.edit', $dept) }}" class="rounded-xl bg-blue-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-blue-700">Редагувати</a>
                        <form method="POST" action="{{ route('admin.support-departments.destroy', $dept) }}" onsubmit="return confirm('Видалити відділ? Тікети залишаться без відділу.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="grid h-9 w-9 place-items-center rounded-xl border border-rose-200 text-rose-500 transition hover:bg-rose-50"><x-icon name="trash" class="h-4 w-4" /></button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-500"><x-icon name="layers" class="h-6 w-6" /></div>
                    <p class="mt-4 text-base font-black text-slate-950">Відділів ще немає</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
