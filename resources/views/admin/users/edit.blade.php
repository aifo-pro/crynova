@extends('layouts.app')
@section('title', 'Користувач · '.$user->email)

@section('content')
@php $roles = ['merchant'=>'Мерчант','support'=>'Техпідтримка','admin'=>'Адміністратор']; @endphp
<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.users.index') }}" class="text-slate-400 hover:text-blue-600"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 text-lg font-bold text-white">{{ strtoupper(substr($user->email,0,1)) }}</span>
            <div>
                <h1 class="text-2xl font-semibold text-slate-950">{{ $user->name }}</h1>
                <p class="text-sm text-slate-500">{{ $user->email }}</p>
            </div>
        </div>
    </div>
    {{-- Block banner --}}
    @unless($user->is_active)
    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
        <p class="font-semibold text-rose-700">Користувача заблоковано {{ $user->blocked_at?->format('d.m.Y H:i') }}</p>
        @if($user->block_reason)<p class="mt-1 text-sm text-rose-600">Причина: {{ $user->block_reason }}</p>@endif
    </div>
    @endunless

    {{-- Full info --}}
    <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/70 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-950">Інформація про користувача</h2>
                <p class="mt-1 text-sm text-slate-500">Обліковий запис, доступ, безпека та активність.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }} px-3 py-1 text-xs font-bold">
                <span class="h-2 w-2 rounded-full {{ $user->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                {{ $user->is_active ? 'Активний' : 'Заблоковано' }}
            </span>
        </div>

        @php
            $infoCards = [
                ['Email', e($user->email), 'font-medium break-all'],
                ['Telegram', $user->telegram ? '@'.e($user->telegram) : '—', 'font-medium break-all'],
                ['Рівень доступу', '<span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">'.($roles[$user->role] ?? $user->role).'</span>', ''],
                ['Кількість кас (проєктів)', number_format($user->merchants_count), 'text-2xl font-black tracking-[-0.03em]'],
                ['2FA', $user->google2fa_enabled ? '<span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Увімкнено</span>' : '<span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">Вимкнено</span>', ''],
                ['Дата реєстрації', $user->created_at->format('d.m.Y H:i'), 'font-semibold'],
                ['Останній вхід', ($user->last_login_at?->format('d.m.Y H:i') ?? 'Ніколи').($user->last_login_ip ? ' · '.e($user->last_login_ip) : ''), 'font-semibold break-words'],
                ['Реферальний код', '<span class="font-mono">'.($user->referral_code ?? '—').'</span>', ''],
            ];
        @endphp

        <div class="grid gap-4 p-6 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($infoCards as [$label, $value, $valueClass])
                <div class="min-h-28 rounded-2xl border border-slate-100 bg-white p-4 shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ $label }}</p>
                    <div class="mt-3 text-sm text-slate-950 {{ $valueClass }}">{!! $value !!}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold text-slate-950">Інформація про користувача</h2>
        @php
            $rows = [
                ['Email', e($user->email)],
                ['Telegram', $user->telegram ? '@'.e($user->telegram) : '—'],
                ['Рівень доступу', '<span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-600">'.($roles[$user->role] ?? $user->role).'</span>'],
                ['Кількість касс (проєктів)', $user->merchants_count],
                ['2FA', $user->google2fa_enabled ? '<span class="text-emerald-600">Увімкнено</span>' : '<span class="text-slate-400">Вимкнено</span>'],
                ['Статус', $user->is_active ? '<span class="font-semibold text-emerald-600">Активний</span>' : '<span class="font-semibold text-rose-600">Заблоковано</span>'],
                ['Дата реєстрації', $user->created_at->format('d.m.Y H:i')],
                ['Останній вхід', ($user->last_login_at?->format('d.m.Y H:i') ?? 'Ніколи').($user->last_login_ip ? ' · '.e($user->last_login_ip) : '')],
                ['Реферальний код', '<span class="font-mono">'.($user->referral_code ?? '—').'</span>'],
            ];
        @endphp
        <div class="grid gap-x-10 sm:grid-cols-2">
            @foreach($rows as $i => [$label, $value])
            <div class="flex items-center justify-between gap-4 border-b border-slate-100 py-3 last:border-0 sm:[&:nth-last-child(2)]:border-0">
                <span class="text-sm text-slate-500">{{ $label }}</span>
                <span class="text-right text-sm font-medium text-slate-900">{!! $value !!}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Balances --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-slate-950">Баланс (по всіх проєктах)</h2>
        @if($balances->isEmpty())
            <p class="text-sm text-slate-400">Балансів немає.</p>
        @else
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach($balances as $code => $bal)
            <div class="rounded-xl border border-slate-200 px-4 py-3">
                <p class="text-sm font-semibold text-blue-600">{{ $code }}</p>
                <p class="mt-1 font-mono text-slate-900">{{ $bal['available'] }}</p>
                @if(bccomp($bal['locked'],'0',18) > 0)<p class="font-mono text-xs text-amber-600">{{ $bal['locked'] }} заблоковано</p>@endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Merchants --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-slate-950">Проєкти користувача ({{ $merchants->count() }})</h2>
        @if($merchants->isEmpty())
            <p class="text-sm text-slate-400">Проєктів немає.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                    <th class="px-3 py-3">Назва</th><th class="px-3 py-3">Тип</th><th class="px-3 py-3">Статус</th><th class="px-3 py-3">Рахунків</th><th class="px-3 py-3">Створено</th>
                </tr></thead>
                <tbody>
                    @foreach($merchants as $m)
                    @php $sm = $m->statusMeta(); @endphp
                    <tr class="border-b border-slate-50 hover:bg-slate-50/60">
                        <td class="px-3 py-3"><a href="{{ route('admin.merchants.show', $m) }}" class="font-semibold text-blue-600 hover:underline">{{ $m->name }}</a></td>
                        <td class="px-3 py-3 text-slate-600">{{ ucfirst($m->merchant_type) }}</td>
                        <td class="px-3 py-3"><span class="text-xs font-semibold text-{{ $sm['color'] }}-600">{{ $sm['label'] }}</span></td>
                        <td class="px-3 py-3 text-slate-700">{{ $m->invoices_count }}</td>
                        <td class="px-3 py-3 text-xs text-slate-400">{{ $m->created_at->format('d.m.Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Management --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-950">Профіль і роль</h2>
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
                @csrf @method('PATCH')
                <div><label class="fin-label">Ім'я</label><input name="name" class="fin-input" value="{{ old('name', $user->name) }}" required></div>
                <div><label class="fin-label">Роль / рівень доступу</label>
                    <select name="role" class="fin-input">
                        @foreach($roles as $v=>$l)<option value="{{ $v }}" @selected($user->role===$v)>{{ $l }}</option>@endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Мерчант — звичайний користувач · Техпідтримка — лише розділи звернень · Адміністратор — повний доступ.</p>
                </div>
                <x-button type="submit" icon="save">Зберегти</x-button>
            </form>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-950">Скидання пароля</h2>
            <form method="POST" action="{{ route('admin.users.password', $user) }}" class="space-y-4">
                @csrf
                <div><label class="fin-label">Новий пароль</label><input name="password" type="password" class="fin-input" required></div>
                <div><label class="fin-label">Підтвердження</label><input name="password_confirmation" type="password" class="fin-input" required></div>
                <x-button type="submit" variant="secondary" icon="lock">Скинути пароль</x-button>
            </form>

            @if($user->google2fa_enabled)
                <hr class="my-5 border-slate-100">
                <h2 class="mb-1 text-lg font-semibold text-slate-950">Скидання 2FA за секретним словом</h2>
                <p class="mb-3 text-sm text-slate-500">
                    Якщо користувач втратив доступ до автентифікатора та звернувся в підтримку — попросіть у нього
                    <strong>секретне слово</strong>, яке він задав під час увімкнення 2FA. Введіть його нижче.
                    2FA буде скинуто лише за точного збігу.
                </p>
                @if(empty($user->tfa_recovery_word))
                    <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700">
                        Цей акаунт увімкнув 2FA до впровадження секретного слова — перевірити його неможливо. Скидання недоступне автоматично, зверніться до техпідтримки бекенду.
                    </p>
                @else
                    <form method="POST" action="{{ route('admin.users.reset-2fa', $user) }}"
                          onsubmit="return confirm('Скинути 2FA для {{ $user->email }}? Дію буде записано в журнал.')"
                          class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div class="flex-1 min-w-56">
                            <label class="fin-label">Секретне слово користувача</label>
                            <input name="recovery_word" type="text" required class="fin-input @error('recovery_word') border-rose-500 @enderror" placeholder="Слово зі звернення">
                            @error('recovery_word')<p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p>@enderror
                        </div>
                        <x-button type="submit" variant="danger" icon="shield-off">Скинути 2FA</x-button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    {{-- Support profile (only relevant for support agents / admins) --}}
    @if(in_array($user->role, ['support', 'admin'], true))
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-lg font-semibold text-slate-950">Профіль техпідтримки</h2>
            <p class="mb-4 text-sm text-slate-500">Ці дані бачить користувач у тікеті та отримує сповіщення агент у Telegram.</p>
            <form method="POST" action="{{ route('admin.users.support-profile', $user) }}" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="fin-label">Публічне ім'я агента</label>
                        <input name="support_display_name" class="fin-input" value="{{ old('support_display_name', $user->support_display_name) }}" placeholder="Напр. Олена, підтримка">
                        <p class="mt-1 text-xs text-slate-400">Показується користувачу замість справжнього імені. Порожнє поле — буде показано ім'я акаунта.</p>
                    </div>
                    <div>
                        <label class="fin-label">Telegram chat ID для сповіщень</label>
                        <input name="support_telegram" class="fin-input" value="{{ old('support_telegram', $user->support_telegram) }}" placeholder="Напр. 123456789">
                        <p class="mt-1 text-xs text-slate-400">Агент отримає сповіщення про призначені тікети та нові повідомлення.</p>
                    </div>
                </div>

                @if($departments->isNotEmpty())
                    <div>
                        <label class="fin-label">Відділи техпідтримки</label>
                        <p class="mb-2 text-xs text-slate-400">Напрями, за які відповідає агент. Він бачить тікети своїх відділів і загального пулу.</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach($departments as $dept)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5 transition hover:bg-slate-50">
                                    <input type="checkbox" name="departments[]" value="{{ $dept->id }}" @checked(in_array($dept->id, old('departments', $userDeptIds))) class="rounded border-slate-300 text-blue-600">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-bold text-slate-800">{{ $dept->name }}</span>
                                        @if($dept->description)<span class="block truncate text-xs text-slate-400">{{ $dept->description }}</span>@endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <x-button type="submit" icon="save">Зберегти профіль</x-button>
            </form>
        </div>
    @endif

    {{-- Notes & tags --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-slate-950">Нотатки та теги</h2>
        @if(!empty($user->tags))
            <div class="mb-3 flex flex-wrap gap-2">
                @foreach($user->tags as $tag)
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $tag }}</span>
                @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('admin.users.notes', $user) }}" class="space-y-3">
            @csrf
            <div>
                <label class="fin-label">Теги <span class="text-slate-400">(через кому, напр. VIP, під наглядом)</span></label>
                <input name="tags" class="fin-input" value="{{ implode(', ', $user->tags ?? []) }}" placeholder="VIP, під наглядом">
            </div>
            <div>
                <label class="fin-label">Внутрішня нотатка</label>
                <textarea name="admin_note" rows="3" class="fin-input" placeholder="Видима лише адміністраторам">{{ $user->admin_note }}</textarea>
            </div>
            <x-button type="submit" icon="save">Зберегти</x-button>
        </form>
    </div>

    {{-- Impersonation --}}
    @unless($user->isAdmin())
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-2 text-lg font-semibold text-slate-950">Вхід від імені користувача</h2>
            <p class="mb-4 text-sm text-slate-500">
                Увійдіть у кабінет цього користувача для діагностики. Дію буде записано в журнал аудиту,
                а повернутися до адмін-акаунту можна кнопкою у верхній панелі.
            </p>
            <form method="POST" action="{{ route('admin.users.impersonate', $user) }}"
                  onsubmit="return confirm('Увійти як {{ $user->email }}?')">
                @csrf
                <x-button type="submit" variant="secondary" icon="user">Увійти як користувач</x-button>
            </form>
        </div>
    @endunless

    {{-- Block / delete --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-slate-950">Блокування та доступ</h2>
        @if($user->isAdmin())
            <p class="text-sm text-slate-500">Адміністратора не можна заблокувати або видалити.</p>
        @else
            @if($user->is_active)
            <form method="POST" action="{{ route('admin.users.block', $user) }}" class="space-y-3">
                @csrf
                <div><label class="fin-label">Причина блокування</label><input name="block_reason" class="fin-input" placeholder="Напр. підозріла активність" required></div>
                <x-button type="submit" variant="danger" icon="shield-off">Заблокувати користувача</x-button>
            </form>
            @else
            <form method="POST" action="{{ route('admin.users.unblock', $user) }}">
                @csrf
                <x-button type="submit" variant="primary" icon="check">Розблокувати користувача</x-button>
            </form>
            @endif

            <hr class="my-4 border-slate-100">
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Видалити користувача?')">
                @csrf @method('DELETE')
                <x-button type="submit" variant="danger" icon="trash">Видалити користувача</x-button>
            </form>
        @endif
    </div>
</div>
@endsection
