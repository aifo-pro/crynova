@props([
    'code' => '',
    'class' => 'h-7 w-7',
])
@php
    $base = strtolower(preg_replace('/[^A-Za-z].*$/', '', explode('_', $code)[0]));
    $available = ['btc', 'eth', 'usdt', 'trx', 'ltc', 'doge'];
    $file = in_array($base, $available, true) ? $base : null;

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
        <span class="flex h-full w-full items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-600">{{ strtoupper(substr($code, 0, 1)) }}</span>
    @endif

    @if($badge)
        <span class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3 items-center justify-center rounded-full {{ $badge[1] }} text-[7px] font-black leading-none text-white ring-2 ring-white">{{ $badge[0] }}</span>
    @endif
</span>
