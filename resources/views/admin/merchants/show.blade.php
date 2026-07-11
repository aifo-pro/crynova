@extends('layouts.app')
@section('title', $merchant->name)

@section('content')
@php
    $sm = $merchant->statusMeta();
    $statusClasses = [
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-200',
    ];
    $badgeClass = $statusClasses[$sm['color']] ?? $statusClasses['slate'];
    $businessLabel = $businessTypes[$merchant->business_type] ?? ($merchant->business_type ?: '—');
    $domainLabel = $merchant->merchant_type === 'telegram'
        ? ($merchant->telegram_channel ? '@'.$merchant->telegram_channel : '—')
        : ($merchant->domain ?: '—');
    $webhookUrl = $merchant->webhook_url ?: $merchant->callback_url;
    $maxChart = max(0.001, ...$analytics['chartRevenue']);
@endphp

<div x-data="{ rejectOpen: false, deleteOpen: false, copied: false }">
    {{-- Header --}}
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <a href="{{ route('admin.merchants.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-600">
                <x-icon name="arrow-left" class="h-4 w-4" /> Назад до списку
            </a>
            <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                <x-icon name="landmark" class="h-3.5 w-3.5" /> Адмін-панель
            </div>
            <h1 class="mt-3 text-3xl font-black tracking-[-0.03em] text-slate-950">{{ $merchant->name }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                Огляд мерчанта, виплат, рахунків та налаштувань з одного екрану.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-button href="{{ route('merchant.control', $merchant) }}" variant="secondary" icon="arrow-right" target="_blank" class="rounded-2xl">
                Панель мерчанта
            </x-button>
            @if($merchant->user?->email)
                <x-button href="mailto:{{ $merchant->user->email }}" variant="secondary" icon="message-circle" class="rounded-2xl">Email</x-button>
            @endif
        </div>
    </div>

    @if(session('new_webhook_secret'))
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-bold">Новий webhook secret (показано один раз):</p>
            <code class="mt-2 block break-all rounded-lg bg-white px-3 py-2 font-mono text-xs">{{ session('new_webhook_secret') }}</code>
        </div>
    @endif

    {{-- Action toolbar --}}
    <div class="mt-8 flex flex-wrap gap-2 rounded-3xl border border-slate-200 bg-white p-3 shadow-sm">
        @if($merchant->isOnModeration())
            <form method="POST" action="{{ route('admin.merchants.approve', $merchant) }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                    <x-icon name="check" class="h-4 w-4" /> Схвалити
                </button>
            </form>
            <button type="button" @click="rejectOpen = true" class="inline-flex items-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-bold text-rose-700 transition hover:bg-rose-100">
                <x-icon name="x" class="h-4 w-4" /> Відмовити
            </button>
        @endif
        @if($merchant->isActive() || $merchant->isBlocked())
            <form method="POST" action="{{ route('admin.merchants.block', $merchant) }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">
                    <x-icon name="shield-off" class="h-4 w-4" />
                    {{ $merchant->isBlocked() ? 'Розблокувати' : 'Заблокувати' }}
                </button>
            </form>
        @endif
        <a href="#invoices" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-blue-200 hover:text-blue-700">
            <x-icon name="file-text" class="h-4 w-4" /> Рахунки
        </a>
        <a href="#tools" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-blue-200 hover:text-blue-700">
            <x-icon name="settings" class="h-4 w-4" /> Інструменти
        </a>
        <button type="button"
                @click="navigator.clipboard.writeText(@js($merchant->paymentPageUrl())); copied = true; setTimeout(() => copied = false, 2000)"
                class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-blue-200 hover:text-blue-700">
            <x-icon name="link" class="h-4 w-4" />
            <span x-text="copied ? 'Скопійовано!' : 'Посилання'"></span>
        </button>
    </div>

    {{-- Reject modal --}}
    <div x-show="rejectOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" @keydown.escape.window="rejectOpen = false">
        <div @click.outside="rejectOpen = false" class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-xl">
            <h3 class="text-lg font-black text-slate-950">Відхилити мерчанта</h3>
            <form method="POST" action="{{ route('admin.merchants.reject', $merchant) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="fin-label">Причина відхилення</label>
                    <textarea name="reject_reason" rows="3" required class="fin-input" placeholder="Опишіть причину…"></textarea>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="danger">Відхилити</x-button>
                    <x-button type="button" variant="secondary" @click="rejectOpen = false">Скасувати</x-button>
                </div>
            </form>
        </div>
    </div>

    {{-- Info grid --}}
    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach([
            ['Назва мерчанта', $merchant->name],
            ['Статус', null],
            ['Власник', $merchant->user?->email ?? '—'],
            ['Вид діяльності', $businessLabel],
            ['Домен / Telegram', $domainLabel],
            ['Дата реєстрації', $merchant->created_at->format('d.m.Y')],
            ['Комісія', $merchant->fee_percent.'%'],
            ['Тип', ucfirst($merchant->merchant_type ?? 'website')],
            ['Рахунків', number_format($invoiceCount)],
        ] as [$label, $value])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ $label }}</p>
                @if($label === 'Статус')
                    <span class="mt-2 inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold ring-1 {{ $badgeClass }}">
                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $sm['label'] }}
                    </span>
                @else
                    <p class="mt-2 text-sm font-bold text-slate-950">{{ $value }}</p>
                @endif
            </div>
        @endforeach
    </div>

    @if($merchant->isRejected() && $merchant->reject_reason)
        <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <span class="font-bold">Причина відхилення:</span> {{ $merchant->reject_reason }}
        </div>
    @endif

    {{-- Base currency --}}
    <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-2">
            <x-icon name="globe" class="h-5 w-5 text-blue-600" />
            <h2 class="text-sm font-black uppercase tracking-[0.12em] text-slate-950">Базова валюта мерчанта</h2>
        </div>
        @if($invoiceCount > 0)
            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                У мерчанта вже є записи в історії (рахунки / платежі: {{ $invoiceCount }}).
                Зміна валюти не змінює старі суми — нові інвойси створюватимуться в новій базовій валюті.
            </div>
        @endif
        <form method="POST" action="{{ route('admin.merchants.base-currency', $merchant) }}" class="mt-4 flex flex-wrap items-end gap-4">
            @csrf
            <div class="min-w-[200px] flex-1">
                <label class="fin-label">Базова валюта</label>
                <select name="base_currency_code" class="fin-input rounded-2xl">
                    @foreach($baseCurrencyOptions as $code)
                        <option value="{{ $code }}" @selected($merchant->base_currency_code === $code)>{{ $code }}</option>
                    @endforeach
                </select>
            </div>
            <x-button type="submit" icon="save" class="rounded-2xl">Зберегти</x-button>
        </form>
    </section>

    <div class="mt-8 grid gap-8 xl:grid-cols-2">
        {{-- Admin note --}}
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="text-lg">📝</span>
                <h2 class="text-sm font-black uppercase tracking-[0.12em] text-slate-950">Нотатка адміна по касі</h2>
            </div>
            <p class="mt-2 text-sm text-slate-500">Тільки для перегляду та редагування адміністратором. Користувач цю нотатку не бачить.</p>
            <form method="POST" action="{{ route('admin.merchants.note', $merchant) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="fin-label">Нотатка</label>
                    <textarea name="admin_note" rows="5" class="fin-input rounded-2xl" placeholder="Внутрішня нотатка по мерчанту…">{{ old('admin_note', $merchant->admin_note) }}</textarea>
                </div>
                <x-button type="submit" icon="save" class="rounded-2xl">Зберегти нотатку</x-button>
            </form>
        </section>

        {{-- Project description --}}
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="text-lg">📋</span>
                <h2 class="text-sm font-black uppercase tracking-[0.12em] text-slate-950">Опис проєкту</h2>
            </div>
            <p class="mt-2 text-sm text-slate-500">Текст, який мерчант вказав при створенні магазину. Бачать модератори; тут можна додати або виправити опис.</p>
            <form method="POST" action="{{ route('admin.merchants.description', $merchant) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="fin-label">Опис проєкту</label>
                    <textarea name="project_description" rows="5" maxlength="1000" required class="fin-input rounded-2xl" placeholder="Опис проєкту…">{{ old('project_description', $merchant->project_description) }}</textarea>
                    <p class="mt-1 text-xs text-slate-400">Не більше 1000 символів.</p>
                </div>
                <x-button type="submit" icon="save" class="rounded-2xl">Зберегти опис</x-button>
            </form>
        </section>
    </div>

    {{-- Balance --}}
    <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="text-lg">💰</span>
            <h2 class="text-sm font-black uppercase tracking-[0.12em] text-slate-950">Баланс мерчанта</h2>
        </div>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-emerald-200 p-5">
                <p class="text-sm font-bold text-emerald-700">Доступний</p>
                <p class="mt-2 text-2xl font-black text-emerald-700">{{ number_format($balanceSummary['available'], 4) }}</p>
                <p class="mt-2 text-xs text-slate-500">Кошти готові до виплати (усі валюти)</p>
            </div>
            <div class="rounded-2xl border border-amber-200 p-5">
                <p class="text-sm font-bold text-amber-700">В обробці</p>
                <p class="mt-2 text-2xl font-black text-amber-700">{{ number_format($balanceSummary['processing'], 4) }}</p>
                <p class="mt-2 text-xs text-slate-500">Очікують підтвердження мережі або завершення платежу</p>
            </div>
            <div class="rounded-2xl border border-rose-200 p-5">
                <p class="text-sm font-bold text-rose-700">Заблоковані</p>
                <p class="mt-2 text-2xl font-black text-rose-700">{{ number_format($balanceSummary['blocked'], 4) }}</p>
                <p class="mt-2 text-xs text-slate-500">Кошти заблоковані (dispute / manual hold)</p>
            </div>
        </div>
        @if($merchant->balances->isNotEmpty())
            <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($merchant->balances as $balance)
                    <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                        <p class="font-bold text-slate-800">{{ $balance->currency->code }}</p>
                        <p class="mt-1 font-mono text-sm text-emerald-700">{{ $balance->available }} доступно</p>
                        <p class="font-mono text-xs text-amber-700">{{ $balance->locked }} заблоковано</p>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Manual balance adjustment --}}
        <details class="mt-6 rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
            <summary class="cursor-pointer text-sm font-black text-slate-700">Ручна корекція балансу</summary>
            <form method="POST" action="{{ route('admin.merchants.adjust-balance', $merchant) }}" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                  onsubmit="return confirm('Підтвердити ручну корекцію балансу?')">
                @csrf
                <div>
                    <label class="fin-label">Валюта</label>
                    <select name="currency_id" class="fin-input" required>
                        @foreach($allCurrencies as $cur)
                            <option value="{{ $cur->id }}">{{ $cur->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fin-label">Напрям</label>
                    <select name="direction" class="fin-input" required>
                        <option value="credit">Нарахувати (+)</option>
                        <option value="debit">Списати (−)</option>
                    </select>
                </div>
                <div>
                    <label class="fin-label">Сума</label>
                    <input name="amount" type="text" inputmode="decimal" class="fin-input font-mono" placeholder="0.00" required>
                </div>
                <div class="flex items-end">
                    <x-button type="submit" icon="banknote" class="w-full">Застосувати</x-button>
                </div>
                <div class="sm:col-span-2 lg:col-span-4">
                    <label class="fin-label">Причина (обовʼязково)</label>
                    <input name="reason" type="text" class="fin-input" placeholder="Напр. компенсація спору #123" required>
                </div>
            </form>
            @error('amount')<p class="mt-2 text-xs font-medium text-rose-500">{{ $message }}</p>@enderror
            @error('reason')<p class="mt-2 text-xs font-medium text-rose-500">{{ $message }}</p>@enderror
        </details>
    </section>

    {{-- Analytics --}}
    <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="text-lg">📊</span>
            <h2 class="text-sm font-black uppercase tracking-[0.12em] text-slate-950">Аналітика</h2>
        </div>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['Оборот сьогодні', number_format($analytics['turnoverToday'], 4).' '.($merchant->base_currency_code ?? 'USD')],
                ['Оборот за місяць', number_format($analytics['turnoverMonth'], 4).' '.($merchant->base_currency_code ?? 'USD')],
                ['Всього платежів', number_format($analytics['totalPayments'])],
            ] as [$label, $value])
                <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.1em] text-slate-400">{{ $label }}</p>
                    <p class="mt-2 text-xl font-black text-slate-950">{{ $value }}</p>
                </div>
            @endforeach
            <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.1em] text-slate-400">Успішні / Неуспішні</p>
                <p class="mt-2 text-xl font-black">
                    <span class="text-emerald-600">{{ $analytics['successful'] }}</span>
                    <span class="text-slate-400"> / </span>
                    <span class="text-rose-600">{{ $analytics['unsuccessful'] }}</span>
                </p>
            </div>
        </div>
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-100 p-5">
                <p class="text-sm font-bold text-slate-700">Оборот по дням (останні 7 днів)</p>
                <div class="mt-4 flex h-40 items-end gap-2">
                    @foreach($analytics['chartRevenue'] as $i => $rev)
                        @php $h = $maxChart > 0 ? max(4, ($rev / $maxChart) * 100) : 4; @endphp
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <div class="w-full rounded-t-lg bg-blue-500/80 transition-all" style="height: {{ $h }}%"></div>
                            <span class="text-[10px] text-slate-400">{{ $analytics['chartLabels'][$i] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="rounded-2xl border border-slate-100 p-5">
                <p class="text-sm font-bold text-slate-700">Розподіл по валютах (оплачені)</p>
                @if($analytics['currencyDistribution']->isEmpty())
                    <p class="mt-6 text-sm text-slate-400">Немає оплачених платежів.</p>
                @else
                    <ul class="mt-4 space-y-3">
                        @foreach($analytics['currencyDistribution'] as $row)
                            <li class="flex items-center justify-between text-sm">
                                <span class="font-bold text-slate-800">{{ $row->code }}</span>
                                <span class="text-slate-500">{{ $row->cnt }} платежів · {{ number_format((float) $row->total, 4) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>

    {{-- Payment methods --}}
    <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ enabled: @js(array_map('intval', $enabledCurrencyIds)) }">
        <div class="flex items-center gap-2">
            <x-icon name="credit-card" class="h-5 w-5 text-blue-600" />
            <h2 class="text-sm font-black uppercase tracking-[0.12em] text-slate-950">Управління платіжними методами</h2>
        </div>
        <form method="POST" action="{{ route('admin.merchants.payment-methods', $merchant) }}" class="mt-6">
            @csrf
            <template x-for="id in enabled" :key="id"><input type="hidden" name="currencies[]" :value="id"></template>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($allCurrencies as $currency)
                    <button type="button"
                            @click="enabled.includes({{ $currency->id }}) ? enabled = enabled.filter(i => i !== {{ $currency->id }}) : enabled.push({{ $currency->id }})"
                            :class="enabled.includes({{ $currency->id }}) ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/20' : 'border-slate-200 hover:border-blue-200'"
                            class="flex items-center gap-3 rounded-2xl border p-4 text-left transition">
                        <x-coin-icon :code="$currency->code" class="h-8 w-8" />
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-slate-900">{{ explode('_', $currency->code)[0] }}</p>
                            <p class="text-xs text-slate-500">{{ $currency->name }}</p>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                              :class="enabled.includes({{ $currency->id }}) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                              x-text="enabled.includes({{ $currency->id }}) ? 'ON' : 'OFF'"></span>
                    </button>
                @endforeach
            </div>
            <div class="mt-6 flex justify-end">
                <x-button type="submit" icon="save" class="rounded-2xl px-8">Зберегти налаштування</x-button>
            </div>
        </form>
    </section>

    {{-- Payment links --}}
    <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-2">
            <x-icon name="link" class="h-5 w-5 text-blue-600" />
            <h2 class="text-sm font-black uppercase tracking-[0.12em] text-slate-950">Платіжні посилання користувача</h2>
        </div>
        @if($merchant->paymentLinks->isEmpty())
            <div class="mt-4 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">
                Користувач ще не створив жодного платіжного посилання для цієї каси.
            </div>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[600px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold uppercase tracking-wide text-slate-400">
                            <th class="py-3 pr-4">Назва</th>
                            <th class="py-3 pr-4">Сума</th>
                            <th class="py-3 pr-4">Використань</th>
                            <th class="py-3 pr-4">Статус</th>
                            <th class="py-3">Посилання</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($merchant->paymentLinks as $link)
                            <tr>
                                <td class="py-3 pr-4 font-semibold text-slate-900">{{ $link->title ?: '—' }}</td>
                                <td class="py-3 pr-4 font-mono">{{ $link->amount ? number_format((float) $link->amount, 4) : '—' }} {{ $link->currency?->code }}</td>
                                <td class="py-3 pr-4">{{ $link->use_count }}{{ $link->max_uses ? ' / '.$link->max_uses : '' }}</td>
                                <td class="py-3 pr-4">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $link->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $link->is_active ? 'Активне' : 'Вимкнено' }}
                                    </span>
                                </td>
                                <td class="py-3"><a href="{{ $link->getPublicUrl() }}" target="_blank" class="text-blue-600 hover:underline">{{ \Illuminate\Support\Str::limit($link->getPublicUrl(), 40) }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Admin tools --}}
    <section id="tools" class="mt-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-2">
            <x-icon name="settings" class="h-5 w-5 text-blue-600" />
            <h2 class="text-sm font-black uppercase tracking-[0.12em] text-slate-950">Інструменти адміністратора</h2>
        </div>
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-100 p-5">
                <p class="flex items-center gap-2 font-bold text-slate-800"><x-icon name="arrow-right" class="h-4 w-4" /> Тест Webhook</p>
                <p class="mt-2 text-sm text-slate-500">Надіслати тестовий POST на:</p>
                <p class="mt-1 break-all font-mono text-xs {{ $webhookUrl ? 'text-slate-700' : 'text-rose-600' }}">
                    {{ $webhookUrl ?: '(webhook_url не налаштовано)' }}
                </p>
                <p class="mt-2 text-xs text-slate-400">Payload підписується секретом мерчанта (HMAC SHA-256).</p>
                @if($webhookUrl)
                    <form method="POST" action="{{ route('admin.merchants.webhook-test', $merchant) }}" class="mt-4">
                        @csrf
                        <x-button type="submit" variant="secondary" icon="arrow-right" class="rounded-2xl">Send test</x-button>
                    </form>
                @else
                    <p class="mt-4 text-xs text-slate-400">Налаштуйте webhook_url або callback_url у мерчанта.</p>
                @endif
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50/40 p-5">
                <p class="flex items-center gap-2 font-bold text-amber-900"><x-icon name="key" class="h-4 w-4" /> Ротація секретного ключа</p>
                <p class="mt-2 text-sm text-amber-900/80">Генерує новий webhook secret. Дія незворотна — мерчанту потрібно оновити інтеграцію.</p>
                <form method="POST" action="{{ route('admin.merchants.rotate-secret', $merchant) }}" class="mt-4" onsubmit="return confirm('Обернути webhook secret? Старий ключ перестане працювати.')">
                    @csrf
                    <x-button type="submit" variant="warning" icon="key" class="rounded-2xl">Rotate key</x-button>
                </form>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.merchants.limits', $merchant) }}" class="mt-6 rounded-2xl border border-slate-100 p-5">
            @csrf
            <p class="flex items-center gap-2 font-bold text-slate-800"><x-icon name="shield" class="h-4 w-4" /> Ліміти мерчанта</p>
            <p class="mt-2 text-sm text-slate-500">Залиште поле порожнім або 0, щоб вимкнути ліміт.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div>
                    <label class="fin-label">Макс. сума рахунку</label>
                    <input type="number" step="0.01" min="0" name="max_invoice_amount" value="{{ old('max_invoice_amount', $merchant->max_invoice_amount) }}" class="fin-input rounded-2xl" placeholder="Без обмеження">
                </div>
                <div>
                    <label class="fin-label">Добовий ліміт обороту</label>
                    <input type="number" step="0.01" min="0" name="daily_turnover_limit" value="{{ old('daily_turnover_limit', $merchant->daily_turnover_limit) }}" class="fin-input rounded-2xl" placeholder="Без обмеження">
                </div>
                <div>
                    <label class="fin-label">Місячний ліміт обороту</label>
                    <input type="number" step="0.01" min="0" name="monthly_turnover_limit" value="{{ old('monthly_turnover_limit', $merchant->monthly_turnover_limit) }}" class="fin-input rounded-2xl" placeholder="Без обмеження">
                </div>
            </div>
            <x-button type="submit" icon="save" class="mt-4 rounded-2xl">Save limits</x-button>
        </form>
    </section>

    {{-- Invoices --}}
    <section id="invoices" class="mt-8 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50/70 px-6 py-5">
            <h2 class="text-lg font-black text-slate-950">Рахунки</h2>
            <p class="mt-1 text-sm text-slate-500">Останні платежі мерчанта.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-xs font-black uppercase tracking-[0.12em] text-slate-400">
                        <th class="px-6 py-4">UUID</th>
                        <th class="px-6 py-4">Валюта</th>
                        <th class="px-6 py-4">Сума</th>
                        <th class="px-6 py-4">Статус</th>
                        <th class="px-6 py-4">Створено</th>
                        <th class="px-6 py-4 text-right">Дія</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $inv)
                        <tr class="transition hover:bg-blue-50/30">
                            <td class="px-6 py-4 font-mono text-xs text-blue-700">{{ \Illuminate\Support\Str::limit($inv->uuid, 12) }}</td>
                            <td class="px-6 py-4 font-semibold">{{ optional($inv->currency)->code ?? $inv->price_currency ?? "—" }}</td>
                            <td class="px-6 py-4 font-mono">{{ $inv->amount }}</td>
                            <td class="px-6 py-4"><x-status-badge :status="$inv->status" /></td>
                            <td class="px-6 py-4 text-slate-500">{{ $inv->created_at->format('d.m.Y H:i') }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.invoices.show', $inv) }}" class="text-xs font-bold text-blue-600 hover:underline">Відкрити</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">Рахунків немає.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">{{ $invoices->links() }}</div>
        @endif
    </section>

    {{-- Danger zone --}}
    <section class="mt-8 rounded-3xl border border-rose-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-2 text-rose-700">
            <x-icon name="trash" class="h-5 w-5" />
            <h2 class="text-sm font-black uppercase tracking-[0.12em]">Видалити касу вручну</h2>
        </div>
        <p class="mt-3 text-sm text-slate-600">
            Видалення каси незворотне. Для підтвердження введіть точну назву мерчанта:
            <strong class="text-slate-900">{{ $merchant->name }}</strong>
        </p>
        <button type="button" @click="deleteOpen = true" class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-rose-700">
            <x-icon name="trash" class="h-4 w-4" /> Видалити касу
        </button>
    </section>

    <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4">
        <div @click.outside="deleteOpen = false" class="w-full max-w-lg rounded-3xl border border-rose-200 bg-white p-6 shadow-xl">
            <h3 class="text-lg font-black text-rose-700">Підтвердження видалення</h3>
            <form method="POST" action="{{ route('admin.merchants.destroy', $merchant) }}" class="mt-4 space-y-4">
                @csrf @method('DELETE')
                <div>
                    <label class="fin-label">Назва мерчанта</label>
                    <input type="text" name="confirm_name" required class="fin-input rounded-2xl" placeholder="{{ $merchant->name }}">
                    @error('confirm_name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="danger" icon="trash">Видалити назавжди</x-button>
                    <x-button type="button" variant="secondary" @click="deleteOpen = false">Скасувати</x-button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
