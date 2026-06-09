@extends('layouts.app')
@section('title', 'Supported Coins')
@section('meta_description', 'Підтримувані криптовалюти та мережі Crynova: BTC, ETH, USDT (TRC20/ERC20/BEP20), USDC, TRX, LTC, DOGE та інші.')

@section('content')
@php
    $coins = [
        ['BTC', 'Bitcoin', 'bitcoin', '3 confirmations', 'Native BTC payments with BIP-style QR data.'],
        ['ETH', 'Ethereum', 'ethereum', '12 confirmations', 'Mainnet ETH rails for EVM users.'],
        ['USDT_ERC20', 'Tether USD', 'ethereum', '12 confirmations', 'Stablecoin payments on Ethereum.'],
        ['USDT_TRC20', 'Tether USD', 'tron', '20 confirmations', 'Fast low-fee stablecoin flow on TRON.'],
        ['USDT_BEP20', 'Tether USD', 'bsc', '15 confirmations', 'BSC stablecoin support for broad wallet compatibility.'],
        ['TRX', 'TRON', 'tron', '20 confirmations', 'Native TRX deposits on TRON.'],
        ['LTC', 'Litecoin', 'litecoin', '6 confirmations', 'Litecoin checkout support.'],
        ['DOGE', 'Dogecoin', 'dogecoin', '6 confirmations', 'DOGE payment acceptance.'],
    ];
@endphp
<section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
    <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
        <div>
            <x-badge variant="teal">Supported coins</x-badge>
            <h1 class="mt-5 text-4xl font-semibold text-white sm:text-5xl">Practical networks merchants ask for</h1>
            <p class="mt-5 text-lg leading-8 text-slate-300">Crynova focuses on high-demand crypto payment rails: Bitcoin, Ethereum, TRON, BSC, Litecoin, Dogecoin and stablecoin flows.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach($coins as [$code, $name, $network, $conf, $desc])
                <div class="rounded-lg border border-slate-800 bg-slate-950/72 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold text-white">{{ $code }}</p>
                            <p class="text-sm text-slate-400">{{ $name }}</p>
                        </div>
                        <x-badge variant="blue">{{ $network }}</x-badge>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-slate-400">{{ $desc }}</p>
                    <p class="mt-4 font-mono text-xs text-teal-200">{{ $conf }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
