<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('checkout.pos.title', ['name' => $merchant->name]) }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 px-4 py-10 text-slate-950">
<main class="mx-auto max-w-md">
    <div class="mb-8 text-center">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-600 text-lg font-black text-white shadow-xl shadow-blue-600/25">C</span>
        <p class="mt-3 font-semibold text-slate-950">{{ $merchant->name }}</p>
        @if($merchant->project_description)
            <p class="mt-2 text-sm text-slate-500">{{ \Illuminate\Support\Str::limit($merchant->project_description, 120) }}</p>
        @endif
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl shadow-slate-200/50">
        @if(session('error'))
            <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('checkout.pos.create', $merchant->shop_id) }}" class="space-y-5">
            @csrf
            <div>
                <label class="fin-label" for="amount">{{ __('checkout.pos.amount') }} <span class="text-rose-500">*</span></label>
                <input id="amount" name="amount" type="number" step="any" min="0.00000001" required class="fin-input text-lg font-semibold @error('amount') border-rose-500 @enderror" value="{{ old('amount') }}" placeholder="0.00">
                @error('amount')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="fin-label" for="currency_id">{{ __('checkout.pos.currency') }} <span class="text-rose-500">*</span></label>
                <select id="currency_id" name="currency_id" required class="fin-input @error('currency_id') border-rose-500 @enderror">
                    <option value="">{{ __('checkout.pos.choose_currency') }}</option>
                    @foreach($currencies as $c)
                        <option value="{{ $c->id }}" @selected(old('currency_id') == $c->id)>{{ $c->code }} — {{ $c->name }}</option>
                    @endforeach
                </select>
                @error('currency_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700 active:scale-[0.98]">
                {{ __('checkout.pos.pay') }}
            </button>
        </form>

        <p class="mt-5 text-center text-xs text-slate-400">
            {{ __('checkout.pos.secured') }} <a href="{{ url('/') }}" class="text-blue-600 hover:underline">Crynova</a> · {{ __('checkout.pos.crypto_payments') }}
        </p>
    </div>
</main>
</body>
</html>
