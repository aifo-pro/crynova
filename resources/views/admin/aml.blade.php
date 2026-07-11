@extends('layouts.app')
@section('title', 'AML / Утримання — Адмін')

@section('content')
<div class="mx-auto w-full max-w-5xl space-y-6">
    <div>
        <div class="inline-flex items-center gap-2 rounded-full border border-amber-100 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">
            <x-icon name="shield" class="h-3.5 w-3.5" /> AML / контроль ризику
        </div>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950">Заблоковані кошти</h1>
        <p class="mt-2 text-sm text-slate-500">Баланси на утриманні (AML hold / dispute). Розблокування переносить кошти в доступні.</p>
    </div>

    <x-card title="Разом на утриманні">
        <p class="text-3xl font-black text-amber-700">{{ number_format($totalHeld, 4) }}</p>
        <p class="mt-1 text-xs text-slate-500">Сума locked по всіх валютах (без конвертації).</p>
    </x-card>

    @if($holds->isEmpty())
        <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-50 text-emerald-600">
                <x-icon name="shield-check" class="h-6 w-6" />
            </div>
            <p class="mt-4 text-base font-black text-slate-950">Немає заблокованих коштів</p>
            <p class="mt-1 text-sm text-slate-500">Усі баланси доступні для виплати.</p>
        </div>
    @else
        <x-card title="Утримання за мерчантами" :subtitle="$holds->count() . ' записів'">
            <div class="divide-y divide-slate-100">
                @foreach($holds as $b)
                    <div class="flex flex-col gap-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <a href="{{ route('admin.merchants.show', $b->merchant) }}" class="font-black text-slate-950 hover:text-blue-700">{{ $b->merchant?->name ?? '—' }}</a>
                            <p class="mt-1 font-mono text-sm text-amber-700">{{ $b->locked }} {{ $b->currency?->code }} заблоковано</p>
                            <p class="font-mono text-xs text-slate-400">{{ $b->available }} {{ $b->currency?->code }} доступно</p>
                        </div>
                        <form method="POST" action="{{ route('admin.aml.release') }}"
                              class="flex flex-wrap items-end gap-2"
                              onsubmit="return confirm('Розблокувати кошти для {{ $b->merchant?->name }}?')">
                            @csrf
                            <input type="hidden" name="merchant_id" value="{{ $b->merchant_id }}">
                            <input type="hidden" name="currency_id" value="{{ $b->currency_id }}">
                            <div>
                                <label class="fin-label">Сума</label>
                                <input name="amount" type="text" inputmode="decimal" value="{{ $b->locked }}" class="fin-input w-36 font-mono" required>
                            </div>
                            <div class="min-w-48 flex-1">
                                <label class="fin-label">Причина</label>
                                <input name="reason" type="text" class="fin-input" placeholder="Напр. перевірку пройдено" required>
                            </div>
                            <x-button type="submit" variant="secondary" icon="check">Розблокувати</x-button>
                        </form>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif
</div>
@endsection
