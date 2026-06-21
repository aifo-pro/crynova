@extends('layouts.app')
@section('title', 'Нова валюта')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.currencies.index') }}" class="grid h-9 w-9 place-items-center rounded-xl border border-slate-200 text-slate-400 transition hover:border-slate-300 hover:text-slate-700"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-2xl font-semibold text-slate-950">Нова валюта</h1>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.currencies.store') }}" class="max-w-2xl">
        @csrf
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="fin-label">Код <span class="text-slate-400">(напр. USDC_ARB)</span></label>
                        <input name="code" type="text" class="fin-input font-mono uppercase @error('code') border-rose-400 @enderror" value="{{ old('code') }}" required placeholder="USDC_ARB">
                        @error('code')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="fin-label">Назва</label>
                        <input name="name" type="text" class="fin-input @error('name') border-rose-400 @enderror" value="{{ old('name') }}" required placeholder="USD Coin (Arbitrum)">
                        @error('name')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="fin-label">Мережа</label>
                    <select name="network" class="fin-input @error('network') border-rose-400 @enderror" required>
                        @foreach($networks as $net)
                            <option value="{{ $net }}" @selected(old('network') === $net)>{{ strtoupper($net) }}</option>
                        @endforeach
                    </select>
                    @error('network')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="fin-label">Адреса контракту <span class="text-slate-400">(для токенів — обовʼязково перевірте!)</span></label>
                    <input name="contract_address" type="text" class="fin-input font-mono text-xs @error('contract_address') border-rose-400 @enderror" value="{{ old('contract_address') }}" placeholder="0x… (порожньо для нативної монети)">
                    @error('contract_address')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-amber-600">⚠️ Невірна адреса контракту = втрата коштів. Беріть лише офіційний контракт токена для обраної мережі.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="fin-label">Знаків після коми</label>
                        <input name="decimals" type="number" min="0" max="18" class="fin-input" value="{{ old('decimals', 18) }}" required>
                    </div>
                    <div>
                        <label class="fin-label">Потрібно підтверджень</label>
                        <input name="confirmations_required" type="number" min="1" class="fin-input" value="{{ old('confirmations_required', 12) }}" required>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="fin-label">Мін. сума</label>
                        <input name="min_amount" type="number" step="any" class="fin-input" value="{{ old('min_amount', '0') }}">
                    </div>
                    <div>
                        <label class="fin-label">Макс. сума</label>
                        <input name="max_amount" type="number" step="any" class="fin-input" value="{{ old('max_amount') }}">
                    </div>
                    <div>
                        <label class="fin-label">Комісія мережі</label>
                        <input name="estimated_fee" type="number" step="any" class="fin-input" value="{{ old('estimated_fee', '0') }}">
                    </div>
                </div>

                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3">
                    <input type="hidden" name="supports_memo" value="0">
                    <input type="checkbox" name="supports_memo" value="1" @checked(old('supports_memo')) class="h-4 w-4 rounded border-slate-300 text-blue-600">
                    <span class="text-sm font-medium text-slate-700">Підтримує memo/tag (TON, деякі мережі)</span>
                </label>

                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="h-4 w-4 rounded border-slate-300 text-blue-600">
                    <span class="text-sm font-medium text-slate-700">Активна (приймає платежі)</span>
                </label>

                <div class="pt-2"><x-button type="submit" icon="save">Створити валюту</x-button></div>
            </div>
        </div>
    </form>
</div>
@endsection
