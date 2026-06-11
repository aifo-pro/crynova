@extends('layouts.app')
@section('title', 'Редагувати валюту')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.currencies.index') }}" class="grid h-9 w-9 place-items-center rounded-xl border border-slate-200 text-slate-400 transition hover:border-slate-300 hover:text-slate-700"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <div class="flex items-center gap-3">
            <x-coin-icon :code="$currency->code" class="h-10 w-10" />
            <div>
                <h1 class="text-2xl font-semibold text-slate-950">{{ $currency->code }}</h1>
                <p class="text-sm text-slate-500">{{ $currency->name }}</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        {{-- Edit form --}}
        <form method="POST" action="{{ route('admin.currencies.update', $currency) }}">
            @csrf @method('PATCH')
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-5 font-semibold text-slate-950">Налаштування валюти</h2>
                <div class="space-y-4">
                    <div>
                        <label class="fin-label">Відображувана назва</label>
                        <input name="name" type="text" class="fin-input @error('name') border-rose-400 @enderror" value="{{ old('name', $currency->name) }}" required>
                        @error('name')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="fin-label">Знаків після коми</label>
                            <input name="decimals" type="number" min="0" max="18" class="fin-input" value="{{ old('decimals', $currency->decimals) }}" required>
                        </div>
                        <div>
                            <label class="fin-label">Потрібно підтверджень</label>
                            <input name="confirmations_required" type="number" min="1" class="fin-input" value="{{ old('confirmations_required', $currency->confirmations_required) }}" required>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="fin-label">Мін. сума</label>
                            <input name="min_amount" type="number" step="any" class="fin-input" value="{{ old('min_amount', $currency->min_amount) }}">
                        </div>
                        <div>
                            <label class="fin-label">Макс. сума</label>
                            <input name="max_amount" type="number" step="any" class="fin-input" value="{{ old('max_amount', $currency->max_amount) }}">
                        </div>
                    </div>

                    <div>
                        <label class="fin-label">Орієнтовна комісія мережі</label>
                        <input name="estimated_fee" type="number" step="any" class="fin-input" value="{{ old('estimated_fee', $currency->estimated_fee) }}">
                        <p class="mt-1 text-xs text-slate-400">Додається до суми рахунку як мережева комісія.</p>
                    </div>

                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $currency->is_active)) class="h-4 w-4 rounded border-slate-300 text-blue-600">
                        <span class="text-sm font-medium text-slate-700">Активна (приймає платежі)</span>
                    </label>

                    <div class="pt-2"><x-button type="submit" icon="save">Зберегти зміни</x-button></div>
                </div>
            </div>
        </form>

        {{-- Read-only info --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 font-semibold text-slate-950">Тільки для читання</h2>
            <dl class="space-y-3.5 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-400">Код</dt>
                    <dd class="font-mono font-bold text-slate-900">{{ $currency->code }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-400">Мережа</dt>
                    <dd><span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-slate-600">{{ strtoupper($currency->network) }}</span></dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-400">Підтримує memo</dt>
                    <dd class="font-semibold {{ $currency->supports_memo ? 'text-emerald-600' : 'text-slate-500' }}">{{ $currency->supports_memo ? 'Так' : 'Ні' }}</dd>
                </div>
                @if($currency->contract_address)
                <div class="border-t border-slate-100 pt-3.5">
                    <dt class="mb-1.5 text-slate-400">Адреса контракту</dt>
                    <dd class="break-all rounded-lg bg-slate-50 px-3 py-2 font-mono text-xs text-slate-600">{{ $currency->contract_address }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>
</div>
@endsection
