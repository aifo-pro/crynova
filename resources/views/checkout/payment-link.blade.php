<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('checkout.proceed') }} {{ $link->title ? '- '.$link->title : '' }} · Crynova</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 px-4 py-10 text-slate-950">
<main class="mx-auto max-w-md">
    <div class="mb-8 text-center">
        <x-logo variant="mark" class="mx-auto h-12 w-12" />
        <p class="mt-3 font-semibold text-slate-950">{{ $link->merchant->name }}</p>
        @if($link->title)
            <h1 class="mt-1 text-2xl font-semibold text-slate-950">{{ $link->title }}</h1>
        @endif
        @if($link->description)
            <p class="mt-2 text-sm text-slate-500">{{ $link->description }}</p>
        @endif
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl shadow-slate-200/50">
        @if(session('error'))
            <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.link.create', $link->token) }}" class="space-y-5">
            @csrf

            @if(! $link->amount)
                <div>
                    <label class="fin-label" for="amount">{{ __('checkout.amount_to_pay') }} <span class="text-rose-500">*</span></label>
                    <input id="amount" name="amount" type="number" step="any" min="0.00000001" class="fin-input text-lg font-semibold @error('amount') border-rose-500 @enderror" placeholder="0.00" value="{{ old('amount') }}" required>
                    @error('amount')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
            @else
                <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-4 text-center">
                    <p class="text-sm text-slate-500">{{ __('checkout.amount') }}</p>
                    <p class="text-3xl font-semibold text-slate-950">
                        {{ $link->amount }}
                        <span class="text-blue-600">{{ $link->currency?->code ?? '' }}</span>
                    </p>
                </div>
            @endif

            @if(! $link->currency_id)
                <div>
                    <label class="fin-label" for="currency_id">{{ __('checkout.currency') }} <span class="text-rose-500">*</span></label>
                    <select id="currency_id" name="currency_id" class="fin-input @error('currency_id') border-rose-500 @enderror" required>
                        <option value="">- {{ __('checkout.choose_currency') }} -</option>
                        @foreach($currencies as $currency)
                            <option value="{{ $currency->id }}" @selected(old('currency_id') == $currency->id)>
                                {{ $currency->code }} - {{ $currency->name }} ({{ strtoupper($currency->network) }})
                            </option>
                        @endforeach
                    </select>
                    @error('currency_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                </div>
            @endif

            @if($link->max_uses)
                <p class="text-center text-xs text-slate-400">
                    {{ $link->use_count }} / {{ $link->max_uses }} {{ __('checkout.uses') }}
                </p>
            @endif

            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700 active:scale-[0.98]">
                {{ __('checkout.proceed') }}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                </svg>
            </button>
        </form>

        <p class="mt-5 text-center text-xs text-slate-400">
            {{ __('checkout.secured_by') }} <a href="{{ url('/') }}" class="text-blue-600 hover:underline">Crynova</a> · {{ __('checkout.crypto_payments') }}
        </p>
    </div>
</main>
</body>
</html>
