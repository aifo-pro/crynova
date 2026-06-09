@extends('layouts.app')
@section('title', 'Users')
@section('content')
<x-card title="Users" subtitle="Platform user management surface.">
    <x-table :headers="['Name', 'Email', 'Role', '2FA', 'Status', 'Last login']">
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Connect to user pagination and filters.</td></tr>
    </x-table>
</x-card>
@endsection
