@props(['status' => 'pending'])
@php
    $map = [
        'paid' => 'green',
        'approved' => 'green',
        'active' => 'green',
        'pending' => 'yellow',
        'waiting_confirmations' => 'blue',
        'processing' => 'blue',
        'overpaid' => 'blue',
        'underpaid' => 'yellow',
        'expired' => 'slate',
        'cancelled' => 'slate',
        'revoked' => 'red',
        'failed' => 'red',
        'rejected' => 'red',
        'refunded' => 'slate',
    ];
    $label = str($status)->replace('_', ' ')->title();
@endphp
<x-badge :variant="$map[$status] ?? 'slate'" {{ $attributes }}>{{ $label }}</x-badge>
