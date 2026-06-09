@extends('layouts.app')
@section('title', 'Wallets')
@section('content')
<x-card title="Wallets" subtitle="Deposit wallet inventory by network and merchant.">
    <x-table :headers="['Address', 'Merchant', 'Currency', 'Network', 'Status', 'Created']">
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Connect to wallet inventory.</td></tr>
    </x-table>
</x-card>
@endsection
