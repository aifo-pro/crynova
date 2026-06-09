@extends('layouts.app')
@section('title', 'Transactions')
@section('content')
<x-card title="Blockchain transactions" subtitle="On-chain transaction tracking and confirmations.">
    <x-table :headers="['Hash', 'Invoice', 'Currency', 'Amount', 'Confirmations', 'Status']">
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Connect to blockchain transaction data.</td></tr>
    </x-table>
</x-card>
@endsection
