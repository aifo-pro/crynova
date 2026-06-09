@extends('layouts.app')
@section('title', __('merchant_settings.currencies.title', ['name' => $merchant->name]))

@section('content')
<div class="space-y-6" x-data="{ enabled: @js(array_map('intval', $enabled)),
        toggle(id){ const i=this.enabled.indexOf(id); if(i===-1)this.enabled.push(id); else this.enabled.splice(i,1); },
        has(id){ return this.enabled.includes(id); } }">
    @include('merchant.settings._tabs')
    <form method="POST" action="{{ route('merchant.settings.currencies.update', $merchant) }}"
          class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @csrf
        <template x-for="id in enabled" :key="id"><input type="hidden" name="currencies[]" :value="id"></template>

        <div class="mb-2 flex items-center gap-2">
            <x-icon name="link" class="h-5 w-5 text-blue-600" />
            <h2 class="text-lg font-semibold text-slate-950">{{ __('merchant_settings.currencies.heading') }}</h2>
        </div>
        <p class="mb-6 text-sm text-slate-500">{{ __('merchant_settings.currencies.text') }}</p>

        <div class="space-y-6 rounded-2xl border border-slate-200 p-4">
            @foreach($grouped as $group)
            <div>
                <p class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-600">
                    <x-icon :name="$group['icon']" class="h-4 w-4 text-slate-400" /> {{ $group['label'] }}
                </p>
                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($group['items'] as $c)
                    <button type="button" @click="toggle({{ $c->id }})"
                            :class="has({{ $c->id }}) ? 'border-blue-500 bg-blue-50/60 ring-2 ring-blue-500/20' : 'border-slate-200 hover:border-blue-200'"
                            class="flex items-center gap-2 rounded-xl border px-3 py-2.5 text-left transition">
                        <x-coin-icon :code="$c->code" class="h-7 w-7" />
                        <span class="text-sm font-semibold text-slate-800">{{ explode('_', $c->code)[0] }}</span>
                        @if(str_contains($c->code,'_') || $c->network)
                        <span class="ml-auto rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-semibold uppercase text-slate-400">{{ \Illuminate\Support\Str::after($c->code,'_') ?: $c->network }}</span>
                        @endif
                    </button>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end"><x-button type="submit" class="rounded-full px-8">{{ __('merchant_settings.common.save') }}</x-button></div>
    </form>
</div>
@endsection
