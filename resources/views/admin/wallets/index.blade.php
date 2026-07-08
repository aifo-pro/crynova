@extends('layouts.app')
@section('title', 'Гаманці')

@section('content')
@php
    $formatCryptoAmount = function ($value): string {
        $value = (string) $value;
        if (str_contains($value, '.')) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        return $value === '' ? '0' : $value;
    };

    $statCards = [
        ['label' => 'Усього адрес', 'value' => $stats['total'] ?? 0, 'icon' => 'wallet', 'tone' => 'blue'],
        ['label' => 'Вільні', 'value' => $stats['free'] ?? 0, 'icon' => 'check', 'tone' => 'emerald'],
        ['label' => 'Привʼязані', 'value' => $stats['used'] ?? 0, 'icon' => 'link', 'tone' => 'amber'],
        ['label' => 'Hot wallets', 'value' => $stats['hot'] ?? 0, 'icon' => 'database', 'tone' => 'cyan'],
    ];

    $toneClasses = [
        'blue' => 'bg-blue-50 text-blue-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'cyan' => 'bg-cyan-50 text-cyan-600',
    ];
@endphp

<div class="space-y-7">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div class="max-w-4xl">
            <x-badge variant="blue">Адмін-панель</x-badge>
            <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950">Гаманці</h1>
            <p class="mt-2 text-base leading-7 text-slate-500">
                Реєстр депозитних адрес за валютою, мережею і типом. Приватні ключі не зберігаються в системі.
            </p>
        </div>
        <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700">
            Показано {{ $wallets->firstItem() ?? 0 }}-{{ $wallets->lastItem() ?? 0 }} з {{ $wallets->total() }}
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($statCards as $card)
            <div class="rounded-[1.35rem] border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/70">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">{{ $card['label'] }}</p>
                        <p class="mt-3 text-3xl font-black tracking-tight text-slate-950">{{ number_format((int) $card['value']) }}</p>
                    </div>
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $toneClasses[$card['tone']] }}">
                        <x-icon :name="$card['icon']" class="h-5 w-5" />
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <section class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/60">
        <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,0.9fr)_minmax(0,0.8fr)_minmax(0,0.8fr)_auto]">
            <div class="relative sm:col-span-2 lg:col-span-1">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    name="search"
                    value="{{ request('search') }}"
                    class="fin-input min-h-14 pl-11"
                    placeholder="Пошук за адресою, memo або invoice UUID..."
                >
            </div>

            <select name="currency" class="fin-input min-h-14">
                <option value="">Валюта: усі</option>
                @foreach($currencies as $currency)
                    <option value="{{ $currency->code }}" @selected(request('currency') === $currency->code)>
                        {{ $currency->code }} - {{ $currency->name }}
                    </option>
                @endforeach
            </select>

            <select name="type" class="fin-input min-h-14">
                <option value="">Тип: усі</option>
                @foreach($types as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </select>

            <select name="status" class="fin-input min-h-14">
                <option value="">Статус: усі</option>
                <option value="free" @selected(request('status') === 'free')>Вільні</option>
                <option value="used" @selected(request('status') === 'used')>Привʼязані</option>
            </select>

            <div class="grid gap-2 sm:col-span-2 sm:grid-cols-2 lg:col-span-1 lg:flex">
                <x-button type="submit" class="min-h-14 w-full lg:w-auto" icon="search">Фільтр</x-button>
                <a href="{{ route('admin.wallets.index') }}" class="inline-flex min-h-14 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                    Скинути
                </a>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
        <div class="flex flex-col gap-2 border-b border-slate-200 bg-slate-50/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-950">Реєстр адрес</h2>
                <p class="mt-1 text-sm text-slate-500">Наведіть на адресу, щоб побачити повністю, або скопіюйте кнопкою.</p>
            </div>
            <span class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Wallet inventory</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-white text-xs uppercase tracking-[0.12em] text-slate-400">
                    <tr>
                        <th class="px-5 py-4 font-black">Адреса</th>
                        <th class="px-4 py-4 font-black">Валюта / мережа</th>
                        <th class="px-4 py-4 font-black">Тип</th>
                        <th class="px-4 py-4 text-right font-black">Баланс</th>
                        <th class="px-4 py-4 font-black">Рахунок</th>
                        <th class="px-4 py-4 font-black">Статус</th>
                        <th class="px-5 py-4 font-black">Перевірено</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($wallets as $wallet)
                        @php
                            $currencyCode = $wallet->currency?->code ?? 'CRYPTO';
                            $network = $wallet->currency?->network
                                ? \Illuminate\Support\Str::of($wallet->currency->network)->replace('_', ' ')->upper()
                                : '—';
                            $typeVariant = match ($wallet->type) {
                                'hot' => 'teal',
                                'cold' => 'blue',
                                'external' => 'slate',
                                default => 'slate',
                            };
                        @endphp
                        <tr class="transition hover:bg-blue-50/30">
                            <td class="w-[20rem] max-w-[20rem] px-5 py-4">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                        <x-icon name="wallet" class="h-4 w-4" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <code class="block truncate font-mono text-[13px] font-bold leading-5 text-blue-700" title="{{ $wallet->address }}">
                                            {{ $wallet->address }}
                                        </code>
                                        @if($wallet->memo)
                                            <p class="mt-1 truncate font-mono text-xs leading-5 text-slate-400" title="Memo: {{ $wallet->memo }}">
                                                Memo: {{ $wallet->memo }}
                                            </p>
                                        @endif
                                    </div>
                                    <button
                                        type="button"
                                        data-copy-text="{{ $wallet->address }}"
                                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-400 transition hover:border-blue-200 hover:text-blue-600"
                                        title="Copy address"
                                    >
                                        <x-icon name="copy" class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <x-coin-icon :code="$currencyCode" class="h-10 w-10" />
                                    <div class="min-w-0">
                                        <p class="break-words font-black leading-5 text-slate-950">{{ $currencyCode }}</p>
                                        <p class="mt-1 break-words text-xs font-semibold uppercase tracking-[0.08em] text-slate-400">{{ $network }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <x-badge :variant="$typeVariant">{{ ucfirst($wallet->type) }}</x-badge>
                            </td>

                            <td class="px-4 py-4 text-right">
                                <p class="break-words font-mono text-sm font-black text-slate-950">
                                    {{ $formatCryptoAmount($wallet->balance) }}
                                    <span class="font-sans font-semibold text-slate-400">{{ $currencyCode }}</span>
                                </p>
                                @if((float) $wallet->balance_unconfirmed > 0)
                                    <p class="mt-1 break-words text-xs font-semibold text-amber-600">
                                        + {{ $formatCryptoAmount($wallet->balance_unconfirmed) }} {{ $currencyCode }} pending
                                    </p>
                                @endif
                            </td>

                            <td class="px-4 py-4">
                                @if($wallet->invoice)
                                    <a href="{{ route('admin.invoices.show', $wallet->invoice) }}" class="inline-flex max-w-[11rem] items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs font-black text-blue-700 transition hover:border-blue-200 hover:bg-blue-100">
                                        <x-icon name="file-text" class="h-3.5 w-3.5 shrink-0" />
                                        <span class="truncate">#{{ substr($wallet->invoice->uuid, 0, 8) }}</span>
                                    </a>
                                @else
                                    <span class="text-sm font-semibold text-slate-400">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-4">
                                @if($wallet->is_used)
                                    <x-badge variant="yellow">Привʼязаний</x-badge>
                                @else
                                    <x-badge variant="green">Вільний</x-badge>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <p class="text-sm font-semibold text-slate-700">
                                    {{ $wallet->last_checked_at?->diffForHumans() ?? 'Ніколи' }}
                                </p>
                                @if($wallet->last_checked_at)
                                    <p class="mt-1 text-xs text-slate-400">{{ $wallet->last_checked_at->format('Y-m-d H:i') }}</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <x-icon name="wallet" class="h-5 w-5" />
                                </span>
                                <p class="mt-4 font-black text-slate-950">Гаманців не знайдено</p>
                                <p class="mt-2 text-sm text-slate-500">Змініть фільтри або пошуковий запит.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-5 py-4">
            {{ $wallets->links() }}
        </div>
    </section>
</div>
@endsection
