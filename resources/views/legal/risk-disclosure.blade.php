@extends('layouts.app')
@section('title', 'Risk Disclosure')
@section('content')
<section class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <x-card title="Risk Disclosure" subtitle="Placeholder disclosure for crypto payment risks.">
        <div class="space-y-4 text-slate-300">
            <p>Digital asset payments involve network fees, volatility, confirmation delays, irreversible transfers, wrong-network risks and wallet custody risks.</p>
            <p>Merchants should clearly communicate accepted currencies, exact amounts, expiry windows and refund policies to customers.</p>
            <p>Crynova dashboards and checkout screens are designed to reduce operational mistakes, but they do not remove blockchain transaction risk.</p>
        </div>
    </x-card>
</section>
@endsection
