@props(['user'])

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        aria-haspopup="true"
        class="user-menu-trigger group inline-flex max-w-[220px] items-center gap-2 rounded-2xl py-1.5 pl-1.5 pr-2.5 sm:max-w-[260px] sm:pr-3"
    >
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 text-sm font-bold text-white shadow-sm shadow-blue-600/20">
            {{ $user['initial'] }}
        </span>

        <span class="hidden min-w-0 flex-1 text-left sm:block">
            <span class="block truncate text-sm font-semibold leading-5 text-slate-900">{{ $user['name'] }}</span>
            <span @class([
                'block truncate text-xs leading-4',
                'font-medium text-emerald-600' => $user['showBalance'],
                'text-slate-500' => ! $user['showBalance'],
            ])>{{ $user['subtitle'] }}</span>
        </span>

        <x-icon
            name="chevron-down"
            class="h-4 w-4 shrink-0 text-slate-400 transition duration-200 group-hover:text-slate-600"
            ::class="open ? 'rotate-180' : ''"
        />
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="user-menu-panel absolute right-0 z-50 mt-2 w-72 origin-top-right overflow-hidden"
    >
        <div class="flex items-start gap-3 border-b border-slate-100 px-4 py-4">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-base font-bold text-white shadow-sm shadow-blue-600/20">
                {{ $user['initial'] }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-slate-900">{{ $user['name'] }}</p>
                <p class="truncate text-xs text-slate-500">{{ $user['email'] }}</p>
                @if($user['isAdmin'])
                    <span class="mt-2 inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-indigo-700">
                        {{ __('ui.administrator') }}
                    </span>
                @elseif($user['showBalance'])
                    <p class="mt-2 text-xs text-slate-500">
                        {{ __('ui.sidebar.balance') }}:
                        <span class="font-semibold text-emerald-600">$ {{ number_format($user['balanceUsd'], 2) }}</span>
                    </p>
                @endif
            </div>
        </div>

        <nav class="p-2">
            <a href="{{ route('account.settings.profile') }}" class="user-menu-item">
                <x-icon name="settings" class="h-4 w-4 text-slate-400" />
                {{ __('ui.settings') }}
            </a>
            <a href="{{ route('account.settings.notifications') }}" class="user-menu-item">
                <x-icon name="bell" class="h-4 w-4 text-slate-400" />
                {{ __('ui.notifications') }}
            </a>
            @if($user['canAdmin'] ?? $user['isAdmin'])
                <a href="{{ route('admin.dashboard') }}" class="user-menu-item">
                    <x-icon name="shield" class="h-4 w-4 text-slate-400" />
                    {{ __('ui.admin_panel') }}
                    @if($user['readonly'] ?? false)<span class="ml-auto rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">read-only</span>@endif
                </a>
            @endif
        </nav>

        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 p-2">
            @csrf
            <button type="submit" class="user-menu-item w-full text-rose-600 hover:bg-rose-50 hover:text-rose-700">
                <x-icon name="log-out" class="h-4 w-4" />
                {{ __('ui.logout') }}
            </button>
        </form>
    </div>
</div>
