@extends('layouts.app')
@section('title', 'Invoices')
@section('content')
<x-card title="Invoices" subtitle="Global invoice monitoring.">
    <x-table :headers="['UUID', 'Merchant', 'Currency', 'Amount', 'Status', 'Created']">
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Connect to global invoice pagination.</td></tr>
    </x-table>
</x-card>
@endsection
