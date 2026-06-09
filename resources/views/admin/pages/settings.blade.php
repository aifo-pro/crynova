@extends('layouts.app')
@section('title', 'Settings')
@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    <x-card title="Platform fees">
        <div class="space-y-4">
            <input class="fin-input" value="0.80%">
            <x-button type="button" variant="secondary">Save</x-button>
        </div>
    </x-card>
    <x-card title="Risk controls">
        <div class="space-y-3 text-sm text-slate-300">
            <label class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/50 p-3">Require 2FA <input type="checkbox" checked></label>
            <label class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/50 p-3">Manual withdrawal review <input type="checkbox" checked></label>
        </div>
    </x-card>
</div>
@endsection
