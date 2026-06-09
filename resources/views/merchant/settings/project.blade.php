@extends('layouts.app')
@section('title', __('merchant_settings.project.title', ['name' => $merchant->name]))

@section('content')
<div class="space-y-6">
    @include('merchant.settings._tabs')
    <form method="POST" action="{{ route('merchant.settings.project.update', $merchant) }}" enctype="multipart/form-data" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @csrf

        <div class="grid gap-4 sm:grid-cols-[1fr_1.4fr] sm:items-center">
            <h2 class="text-base font-semibold text-slate-950">{{ __('merchant_settings.project.permanent_link') }}</h2>
            <div class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5">
                <a href="{{ $merchant->paymentPageUrl() }}" target="_blank" class="flex-1 truncate text-sm text-blue-600 hover:underline">{{ $merchant->paymentPageUrl() }}</a>
                <button type="button" class="text-slate-300 hover:text-blue-600" data-copy-text="{{ $merchant->paymentPageUrl() }}"><x-icon name="copy" class="h-4 w-4" /></button>
            </div>
        </div>

        <hr class="my-6 border-slate-100">

        <div class="grid gap-4 sm:grid-cols-[1fr_1.4fr] sm:items-start">
            <div>
                <h2 class="text-base font-semibold text-slate-950">{{ __('merchant_settings.project.name') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('merchant_settings.project.name_text') }}</p>
            </div>
            <input name="name" type="text" minlength="3" maxlength="60" required value="{{ old('name', $merchant->name) }}" class="fin-input">
        </div>

        <hr class="my-6 border-slate-100">

        <div class="grid gap-4 sm:grid-cols-[1fr_1.4fr] sm:items-start">
            <div>
                <h2 class="text-base font-semibold text-slate-950">{{ __('merchant_settings.project.business_type') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('merchant_settings.project.business_text') }}</p>
            </div>
            <input name="business_type" type="text" required value="{{ old('business_type', $merchant->business_type) }}" class="fin-input" placeholder="{{ __('merchant_settings.project.business_placeholder') }}">
        </div>

        <hr class="my-6 border-slate-100">

        <div class="grid gap-4 sm:grid-cols-[1fr_1.4fr] sm:items-start" x-data="{ d: @js(old('project_description', $merchant->project_description)) }">
            <div>
                <h2 class="text-base font-semibold text-slate-950">{{ __('merchant_settings.project.description') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('merchant_settings.project.description_text') }}</p>
            </div>
            <div>
                <textarea name="project_description" x-model="d" rows="4" minlength="100" maxlength="250" required class="fin-input"></textarea>
                <div class="mt-1 flex justify-between text-xs text-slate-400">
                    <div>
                        <p>{{ __('merchant_settings.project.min_chars') }}</p>
                        <p>{{ __('merchant_settings.project.max_chars') }}</p>
                    </div>
                    <span :class="d.length < 100 ? 'text-rose-400' : 'text-emerald-500'" x-text="d.length"></span>
                </div>
            </div>
        </div>

        <hr class="my-6 border-slate-100">

        <div class="grid gap-4 sm:grid-cols-[1fr_1.4fr] sm:items-start">
            <div>
                <h2 class="text-base font-semibold text-slate-950">{{ __('merchant_settings.project.logo') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('merchant_settings.project.logo_text') }}</p>
            </div>
            <div>
                @if($merchant->logo_path)
                    <img src="{{ asset('storage/'.$merchant->logo_path) }}" alt="logo" class="mb-2 h-12 rounded-lg border border-slate-200">
                @endif
                <input name="logo" type="file" accept="image/png,image/jpeg" class="fin-input file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-blue-600">
                @error('logo')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
        </div>

        <hr class="my-6 border-slate-100">

        <div class="grid gap-4 sm:grid-cols-[1fr_1.4fr] sm:items-start">
            <div>
                <h2 class="text-base font-semibold text-slate-950">{{ __('merchant_settings.project.management') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('merchant_settings.project.management_text') }}</p>
            </div>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ __('merchant_settings.project.test_mode') }}</span>
                    <span class="flex items-center gap-2">
                        <span class="text-sm font-semibold {{ $merchant->test_mode ? 'text-blue-600' : 'text-slate-400' }}">{{ $merchant->test_mode ? __('merchant_settings.common.enabled') : __('merchant_settings.common.disabled') }}</span>
                        <button type="submit" form="testModeForm" role="switch" class="relative inline-flex h-5 w-9 items-center rounded-full transition {{ $merchant->test_mode ? 'bg-blue-600' : 'bg-slate-200' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition {{ $merchant->test_mode ? 'translate-x-4' : 'translate-x-1' }}"></span>
                        </button>
                    </span>
                </div>

                @php $sm = $merchant->statusMeta(); @endphp
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ __('merchant_settings.project.status') }}</span>
                    <span class="text-sm font-semibold text-{{ $sm['color'] }}-600">{{ $sm['label'] }}</span>
                </div>

                @if($merchant->isUnverified() || $merchant->isRejected())
                    <a href="{{ route('merchant.verification', $merchant) }}" class="inline-flex text-sm font-semibold text-blue-600 hover:underline">{{ __('merchant_settings.project.verify') }}</a>
                @endif

                <button type="submit" form="deleteForm" class="text-sm font-semibold text-rose-500 hover:underline">{{ __('merchant_settings.project.delete') }}</button>
            </div>
        </div>

        <hr class="my-6 border-slate-100">
        <div class="flex justify-end">
            <x-button type="submit" class="rounded-full px-8">{{ __('merchant_settings.common.save') }}</x-button>
        </div>
    </form>

    <form id="testModeForm" method="POST" action="{{ route('merchant.test-mode', $merchant) }}" class="hidden">@csrf</form>
    <form id="deleteForm" method="POST" action="{{ route('merchant.settings.destroy', $merchant) }}" class="hidden" onsubmit="return confirm('{{ __('merchant_settings.project.delete_confirm') }}')">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection
