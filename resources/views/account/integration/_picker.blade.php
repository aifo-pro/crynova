@if($merchants->count() > 1)
    <form method="GET" class="flex items-center gap-2">
        <span class="text-sm text-slate-500">{{ __('account.integration.project') }}:</span>
        <select name="project" onchange="this.form.submit()" class="fin-input w-64">
            @foreach($merchants as $item)
                <option value="{{ $item->id }}" @selected($merchant && $merchant->id === $item->id)>{{ $item->name }}</option>
            @endforeach
        </select>
    </form>
@elseif($merchant)
    <span class="text-sm text-slate-500">{{ __('account.integration.project') }}: <span class="font-semibold text-slate-950">{{ $merchant->name }}</span></span>
@endif
