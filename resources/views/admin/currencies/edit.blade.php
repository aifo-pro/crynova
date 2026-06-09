@extends('layouts.app')
@section('title', 'Редагувати валюту')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.currencies.index') }}" class="text-slate-400 hover:text-white"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-white">{{ $currency->code }} — {{ $currency->name }}</h1>
    </div>
    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.currencies.update', $currency) }}" class="space-y-6">
            @csrf @method('PATCH')

            <x-card title="Налаштування валюти">
                <div class="space-y-4">
                    <div>
                        <label class="fin-label">Відображувана назва</label>
                        <input name="name" type="text" class="fin-input" value="{{ old('name', $currency->name) }}" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="fin-label">Знаків після коми</label>
                            <input name="decimals" type="number" min="0" max="18" class="fin-input"
                                   value="{{ old('decimals', $currency->decimals) }}" required>
                        </div>
                        <div>
                            <label class="fin-label">Потрібно підтверджень</label>
                            <input name="confirmations_required" type="number" min="1" class="fin-input"
                                   value="{{ old('confirmations_required', $currency->confirmations_required) }}" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="fin-label">Мін. сума</label>
                            <input name="min_amount" type="number" step="any" class="fin-input"
                                   value="{{ old('min_amount', $currency->min_amount) }}">
                        </div>
                        <div>
                            <label class="fin-label">Макс. сума</label>
                            <input name="max_amount" type="number" step="any" class="fin-input"
                                   value="{{ old('max_amount', $currency->max_amount) }}">
                        </div>
                    </div>

                    <div>
                        <label class="fin-label">Орієнтовна комісія мережі</label>
                        <input name="estimated_fee" type="number" step="any" class="fin-input"
                               value="{{ old('estimated_fee', $currency->estimated_fee) }}">
                    </div>

                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $currency->is_active))
                               class="rounded border-slate-700 text-teal-400">
                        <span class="text-sm text-slate-300">Активна (приймає платежі)</span>
                    </label>

                    <x-button type="submit" icon="save">Зберегти зміни</x-button>
                </div>
            </x-card>
        </form>

        <x-card title="Тільки для читання">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-400">Код</dt>
                    <dd class="font-mono text-teal-200">{{ $currency->code }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Мережа</dt>
                    <dd class="text-slate-200">{{ strtoupper($currency->network) }}</dd>
                </div>
                @if($currency->contract_address)
                <div>
                    <dt class="mb-1 text-slate-400">Адреса контракту</dt>
                    <dd class="break-all font-mono text-xs text-slate-300">{{ $currency->contract_address }}</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-slate-400">Підтримує memo</dt>
                    <dd class="text-slate-200">{{ $currency->supports_memo ? 'Так' : 'Ні' }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</div>
@endsection
