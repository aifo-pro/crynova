<div x-data="{ show: false }"
     x-init="show = ! localStorage.getItem('crynova_cookie_consent')"
     x-show="show" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="translate-y-4 opacity-0"
     x-transition:enter-end="translate-y-0 opacity-100"
     class="fixed inset-x-0 bottom-0 z-[60] px-4 pb-4 sm:px-6 sm:pb-6">
    <div class="mx-auto flex max-w-4xl flex-col gap-4 rounded-2xl border border-slate-200 bg-white/95 p-5 shadow-2xl shadow-slate-300/40 backdrop-blur sm:flex-row sm:items-center sm:gap-6 sm:p-6">
        <div class="flex items-start gap-3.5">
            <span class="hidden h-11 w-11 shrink-0 place-items-center rounded-2xl bg-blue-50 text-blue-600 sm:grid">
                <x-icon name="shield" class="h-5 w-5" />
            </span>
            <p class="text-sm leading-6 text-slate-600">
                {{ __('public.cookies.text') }}
                <a href="{{ url('/privacy') }}" class="font-semibold text-blue-600 underline hover:text-blue-700">{{ __('public.cookies.link') }}</a>.
            </p>
        </div>
        <div class="flex shrink-0 items-center gap-2.5 sm:ml-auto">
            <button type="button"
                    @click="localStorage.setItem('crynova_cookie_consent', 'declined'); show = false"
                    class="rounded-full border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                {{ __('public.cookies.decline') }}
            </button>
            <button type="button"
                    @click="localStorage.setItem('crynova_cookie_consent', 'accepted'); show = false"
                    class="rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:opacity-90">
                {{ __('public.cookies.accept') }}
            </button>
        </div>
    </div>
</div>
