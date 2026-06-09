@extends('layouts.app')
@section('title', 'Pricing')
@section('meta_description', 'Прозрачные тарифы Crynova: комиссия за приём криптоплатежей, выводы и расчёты без скрытых платежей.')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
    <div class="max-w-3xl">
        <x-badge variant="teal">Transparent pricing</x-badge>
        <h1 class="mt-5 text-4xl font-semibold text-white sm:text-5xl">Simple fees for crypto payment operations</h1>
        <p class="mt-5 text-lg leading-8 text-slate-300">Use hosted checkout, API invoices, webhook delivery and merchant dashboards without a complicated pricing matrix.</p>
    </div>

    <div class="mt-10 grid gap-4 lg:grid-cols-3">
        @foreach([
            ['Starter', 'For early-stage merchants validating crypto checkout.', '0.8%', ['Hosted checkout', 'Invoice API', 'Basic webhook retries', 'Merchant dashboard']],
            ['Growth', 'For teams running recurring payment operations.', 'Custom', ['Priority webhook delivery', 'Higher rate limits', 'Withdrawal workflows', 'Operational reporting']],
            ['Enterprise', 'For high-volume or regulated business flows.', 'Custom', ['Dedicated support', 'Custom risk controls', 'Advanced audit exports', 'Network rollout planning']],
        ] as [$name, $desc, $price, $features])
            <x-card class="{{ $loop->first ? 'border-teal-400/40' : '' }}">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-white">{{ $name }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-400">{{ $desc }}</p>
                    </div>
                    @if($loop->first)<x-badge variant="teal">Popular</x-badge>@endif
                </div>
                <p class="mt-6 text-4xl font-semibold text-white">{{ $price }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $price === '0.8%' ? 'per paid invoice, network fees excluded' : 'volume-based terms' }}</p>
                <div class="mt-6 space-y-3">
                    @foreach($features as $feature)
                        <div class="flex items-center gap-3 text-sm text-slate-300">
                            <x-icon name="check" class="h-4 w-4 text-teal-300" />
                            {{ $feature }}
                        </div>
                    @endforeach
                </div>
                <x-button href="{{ route('register') }}" class="mt-7 w-full">Start now</x-button>
            </x-card>
        @endforeach
    </div>

    <x-card class="mt-10" title="What is included" subtitle="Designed for real payment operations, not only a payment button.">
        <div class="grid gap-4 md:grid-cols-4">
            @foreach([
                ['qr', 'Hosted checkout'],
                ['key', 'API keys'],
                ['link', 'Signed webhooks'],
                ['shield', '2FA & audit logs'],
                ['wallet', 'Balances'],
                ['banknote', 'Withdrawals'],
                ['file-text', 'Invoice tables'],
                ['gauge', 'Admin metrics'],
            ] as [$icon, $label])
                <div class="rounded-lg border border-slate-800 bg-slate-900/50 p-4">
                    <x-icon :name="$icon" class="h-5 w-5 text-teal-300" />
                    <p class="mt-3 font-medium text-white">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </x-card>
</section>
@endsection
