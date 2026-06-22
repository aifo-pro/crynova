@php
    $myMerchants = auth()->user()?->accessibleMerchants()->latest()->get() ?? collect();
    $inProjects = request()->routeIs('merchant.*') || request()->routeIs('account.projects') || request()->routeIs('account.merchants.*');
    $inIntegration = request()->routeIs('account.integration.*');
    $currentMid = request()->routeIs('merchant.*') ? (int) optional(request()->route('merchant'))->id ?? optional($currentMerchant ?? null)->id : null;
    $telegramBotUrl = trim((string) \App\Models\Setting::get('telegram_bot_url', ''));
@endphp

<aside class="lg:sticky lg:top-24 lg:self-start" x-data="{
    projects: {{ $inProjects ? 'true' : 'false' }},
    integration: {{ $inIntegration ? 'true' : 'false' }}
}">
    <div class="rounded-3xl border border-slate-200 bg-white p-3 shadow-xl shadow-slate-200/50">
        <nav class="space-y-1 text-sm">
            @foreach([
                ['account.dashboard', __('ui.sidebar.main'), 'gauge'],
                ['account.balance', __('ui.sidebar.balance'), 'wallet'],
                ['account.payments', __('ui.sidebar.payments'), 'file-text'],
            ] as [$route, $label, $icon])
                @php $active = request()->routeIs($route); @endphp
                <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-2xl px-3 py-2.5 font-medium transition {{ $active ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                    <x-icon :name="$icon" class="h-4 w-4" /> {{ $label }}
                </a>
            @endforeach

            <div>
                <button type="button" @click="projects = !projects" class="flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-950">
                    <x-icon name="layers" class="h-4 w-4" /> {{ __('ui.sidebar.projects') }}
                    <x-icon name="chevron-down" class="ml-auto h-4 w-4 transition" ::class="projects ? 'rotate-180' : ''" />
                </button>
                <div x-show="projects" class="mt-1 space-y-0.5 pl-4">
                    @forelse($myMerchants as $merchant)
                        @php $isCurrent = $currentMid === $merchant->id; @endphp
                        <a href="{{ route('merchant.settings.project', $merchant) }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-[13px] transition {{ $isCurrent ? 'bg-blue-50 font-semibold text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $merchant->isActive() ? 'bg-emerald-500' : ($merchant->isOnModeration() ? 'bg-amber-500' : 'bg-slate-300') }}"></span>
                            <span class="truncate">{{ $merchant->name }}</span>
                        </a>
                    @empty
                        <p class="px-3 py-1.5 text-[12px] text-slate-400">{{ __('ui.sidebar.no_projects') }}</p>
                    @endforelse
                    <a href="{{ route('account.merchants.create') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-[13px] font-semibold text-blue-600 hover:bg-blue-50">
                        <x-icon name="plus" class="h-3.5 w-3.5" /> {{ __('ui.sidebar.add_project') }}
                    </a>
                </div>
            </div>

            <div>
                <button type="button" @click="integration = !integration" class="flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-950">
                    <x-icon name="link" class="h-4 w-4" /> {{ __('ui.sidebar.integration') }}
                    <x-icon name="chevron-down" class="ml-auto h-4 w-4 transition" ::class="integration ? 'rotate-180' : ''" />
                </button>
                <div x-show="integration" class="mt-1 space-y-0.5 pl-4">
                    @foreach([
                        ['account.integration.api', 'API'],
                        ['account.integration.modules', __('ui.sidebar.modules')],
                        ['account.integration.widget', __('ui.sidebar.widget_settings')],
                        ['account.integration.brandbook', __('ui.sidebar.brandbook')],
                    ] as [$route, $label])
                        @php $active = request()->routeIs($route); @endphp
                        <a href="{{ route($route) }}" class="block rounded-xl px-3 py-2 text-[13px] transition {{ $active ? 'bg-blue-50 font-semibold text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('account.exchange') }}" class="flex items-center gap-3 rounded-2xl px-3 py-2.5 font-medium transition {{ request()->routeIs('account.exchange') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                <x-icon name="coins" class="h-4 w-4" /> {{ __('ui.sidebar.exchange') }}
            </a>
        </nav>

        <div class="mt-3 space-y-1 border-t border-slate-100 pt-3 text-sm">
            @php $supportUnread = \App\Models\SupportTicket::where('user_id', auth()->id())->where('user_unread', true)->count(); @endphp
            <a href="{{ route('account.support.index') }}" class="flex items-center gap-3 rounded-2xl px-3 py-2.5 transition {{ request()->routeIs('account.support.*') ? 'bg-blue-50 font-semibold text-blue-600' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                <x-icon name="message-circle" class="h-4 w-4" /> {{ __('ui.sidebar.support') }}
                @if($supportUnread > 0)<span class="ml-auto grid h-5 min-w-5 place-items-center rounded-full bg-blue-600 px-1.5 text-[11px] font-bold text-white">{{ $supportUnread }}</span>@endif
            </a>
            <a href="{{ route('account.knowledge') }}" class="flex items-center gap-3 rounded-2xl px-3 py-2.5 transition {{ request()->routeIs('account.knowledge') ? 'bg-blue-50 font-semibold text-blue-600' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}"><x-icon name="book" class="h-4 w-4" /> {{ __('ui.sidebar.knowledge') }}</a>
            <a href="{{ route('account.partner') }}" class="flex items-center gap-3 rounded-2xl px-3 py-2.5 transition {{ request()->routeIs('account.partner') ? 'bg-blue-50 font-semibold text-blue-600' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}"><x-icon name="user" class="h-4 w-4" /> {{ __('ui.sidebar.partner') }}</a>
            <a href="{{ $telegramBotUrl !== '' ? $telegramBotUrl : route('contact') }}" @if($telegramBotUrl !== '') target="_blank" rel="noopener" @endif class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-slate-600 hover:bg-slate-50 hover:text-slate-950"><x-icon name="sparkles" class="h-4 w-4" /> {{ __('ui.sidebar.send_idea') }}</a>
        </div>

        <div class="mt-3 flex justify-center border-t border-slate-100 pt-3">
            <x-language-switcher compact />
        </div>
    </div>
</aside>
