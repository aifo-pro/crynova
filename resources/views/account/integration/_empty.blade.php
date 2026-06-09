<div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
    <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600"><x-icon name="landmark" class="h-7 w-7" /></span>
    <p class="text-lg font-semibold text-slate-950">{{ __('account.integration.empty_title') }}</p>
    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ __('account.integration.empty_text') }}</p>
    <x-button href="{{ route('account.merchants.create') }}" icon="plus" class="mt-5">{{ __('account.projects.add') }}</x-button>
</div>
