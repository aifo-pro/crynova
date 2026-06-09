@extends('layouts.app')
@section('title', 'Настройки виджета')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-950">Настройки виджета</h1>
            <p class="mt-1 text-slate-500">Вбудовуваний платіжний віджет для вашого сайту.</p>
        </div>
        @if($merchant)@include('account.integration._picker')@endif
    </div>

    @if(! $merchant)
        @include('account.integration._empty')
    @else
        <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600"><x-icon name="layout" class="h-7 w-7" /></span>
            <p class="text-lg font-semibold text-slate-950">Конструктор виджета для «{{ $merchant->name }}»</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">Настройте кнопку, стиль и получите код для встраивания в конструкторе виджета проекта.</p>
            <x-button href="{{ route('merchant.settings.widget', $merchant) }}" icon="layout" class="mt-5">Відкрити конструктор віджета</x-button>
        </div>
    @endif
</div>
@endsection
