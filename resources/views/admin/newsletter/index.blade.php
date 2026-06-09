@extends('layouts.app')
@section('title', 'Email розсилка')

@section('content')
<div>
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                <x-icon name="message-circle" class="h-3.5 w-3.5" />
                Адмін-панель
            </div>
            <h1 class="mt-4 text-3xl font-black tracking-[-0.03em] text-slate-950">Email розсилка</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">Відправка повідомлень активним користувачам з обов'язковим посиланням відписки.</p>
        </div>
    </div>

    @if(session('success'))
        <x-alert variant="success" class="mt-6">{{ session('success') }}</x-alert>
    @endif
    @if($errors->any())
        <x-alert variant="error" class="mt-6">{{ $errors->first() }}</x-alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]" style="margin-top: 32px;">
        <form method="POST" action="{{ route('admin.newsletter.send') }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div class="mb-6">
                <h2 class="text-lg font-black text-slate-950">Нова розсилка</h2>
                <p class="mt-1 text-sm text-slate-500">Текст буде відправлений як безпечний HTML з автоматичною кнопкою відписки.</p>
            </div>
            <div class="space-y-5">
                <div>
                    <label class="fin-label">Тема листа</label>
                    <input name="subject" value="{{ old('subject') }}" maxlength="180" required class="fin-input" placeholder="Наприклад: Оновлення Crynova">
                </div>
                <div>
                    <label class="fin-label">Текст листа</label>
                    <textarea name="body" rows="10" required class="fin-input" placeholder="Напишіть повідомлення користувачам...">{{ old('body') }}</textarea>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                    Розсилка не буде відправлена користувачам, які вже відписалися. У кожному листі буде посилання для відписки.
                </div>
                <x-button type="submit" icon="message-circle" class="rounded-full px-8">Відправити всім</x-button>
            </div>
        </form>

        <aside class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Отримувачі</p>
                <p class="mt-3 text-3xl font-black text-slate-950">{{ number_format($eligibleUsers) }}</p>
                <p class="mt-1 text-sm text-slate-500">Активні користувачі без відписки</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Відписались</p>
                <p class="mt-3 text-3xl font-black text-slate-950">{{ number_format($unsubscribed) }}</p>
                <p class="mt-1 text-sm text-slate-500">Не отримують масові листи</p>
            </div>
        </aside>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" style="margin-top: 34px;">
        <div class="border-b border-slate-100 bg-slate-50/70 px-6 py-5">
            <h2 class="text-lg font-black text-slate-950">Останні розсилки</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left">
                <thead>
                    <tr class="border-b border-slate-100 text-xs font-black uppercase tracking-[0.12em] text-slate-400">
                        <th class="px-6 py-4">Тема</th>
                        <th class="px-6 py-4">Відправив</th>
                        <th class="px-6 py-4">Отримувачі</th>
                        <th class="px-6 py-4">Дата</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($mailings as $mailing)
                        <tr>
                            <td class="px-6 py-4 font-bold text-slate-950">{{ $mailing->subject }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $mailing->sender?->email ?? 'system' }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-slate-950">{{ $mailing->recipients_count }}</td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ $mailing->sent_at?->format('d.m.Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500">Розсилок поки немає.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
