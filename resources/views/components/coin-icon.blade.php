@props([
    'code' => '',
    'class' => 'h-7 w-7',
])
@php
    $base = strtolower(preg_replace('/[^A-Za-z].*$/', '', explode('_', $code)[0]));
    $available = ['btc', 'eth', 'usdt', 'trx', 'ltc', 'doge'];
    $file = in_array($base, $available, true) ? $base : null;

    // Brand colour for the lettered fallback chip (coins without an SVG logo).
    $brand = [
        'usdc' => '#2775ca', 'sol' => '#9945ff', 'ton' => '#0098ea', 'bnb' => '#f3ba2f',
        'dai' => '#f5ac37', 'shib' => '#f00500', 'pepe' => '#4a9b3c', 'pyusd' => '#0070ba',
        'xaut' => '#d4af37', 'usdd' => '#1ec99a', 'arb' => '#28a0f0', 'op' => '#ff0420',
        'trump' => '#c79a3b', 'busd' => '#f0b90b', 'tusd' => '#1a5aff', 'matic' => '#8247e5',
        'pol' => '#8247e5',
    ];
    $brandColor = $brand[$base] ?? '#64748b';
    $brandLabel = strtoupper(substr($base, 0, 4));

    // Network suffix (e.g. USDT_TRC20 → TRC20). For native coins there is no suffix.
    $net = \Illuminate\Support\Str::after($code, '_');
    $net = $net === $code ? '' : strtoupper($net);

    // Map network → short label + colour
    $netMeta = [
        'TRC20' => ['T', 'bg-red-500'],
        'ERC20' => ['E', 'bg-indigo-500'],
        'BEP20' => ['B', 'bg-yellow-500'],
        'BSC'   => ['B', 'bg-yellow-500'],
        'SOL'   => ['S', 'bg-purple-500'],
        'TON'   => ['T', 'bg-sky-500'],
        'ARB'   => ['A', 'bg-blue-500'],
        'OPT'   => ['O', 'bg-rose-500'],
        'BASE'  => ['B', 'bg-blue-600'],
        'MATIC' => ['M', 'bg-violet-500'],
        'POL'   => ['P', 'bg-violet-500'],
    ];
    $badge = $netMeta[$net] ?? ($net !== '' ? [substr($net, 0, 1), 'bg-slate-400'] : null);
@endphp
<span {{ $attributes->merge(['class' => 'relative inline-flex shrink-0 '.$class]) }}>
    @if($file)
        <img src="{{ asset('assets/crynova/crypto-icons/'.$file.'.svg') }}" alt="{{ $code }}" class="h-full w-full rounded-full">
    @else
        <span class="flex h-full w-full items-center justify-center rounded-full text-[8px] font-black leading-none text-white" style="background-color: {{ $brandColor }}">{{ $brandLabel }}</span>
    @endif

    @if($badge)
        <span class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3 items-center justify-center rounded-full {{ $badge[1] }} text-[7px] font-black leading-none text-white ring-2 ring-white">{{ $badge[0] }}</span>
    @endif
</span>
