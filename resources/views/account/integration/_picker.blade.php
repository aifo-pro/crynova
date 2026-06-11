@if($merchants->count() > 1)
    <form method="GET" class="flex items-center gap-2">
        <span class="text-sm text-slate-500">{{ __('account.integration.project') }}:</span>
        <div class="w-64"><x-project-select name="project" :projects="$merchants" :selected="$merchant?->id" submit /></div>
    </form>
@elseif($merchant)
    <span class="text-sm text-slate-500">{{ __('account.integration.project') }}: <span class="font-semibold text-slate-950">{{ $merchant->name }}</span></span>
@endif
