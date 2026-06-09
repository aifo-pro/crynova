@extends('layouts.app')
@section('title', 'Audit Logs')
@section('content')
<x-card title="Audit logs" subtitle="Security and operations event stream.">
    <x-table :headers="['Actor', 'Action', 'Subject', 'Changes', 'Created']">
        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Connect to audit log pagination.</td></tr>
    </x-table>
</x-card>
@endsection
