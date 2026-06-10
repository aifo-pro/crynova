@extends('layouts.app')
@section('title', $ticket->subject)

@section('content')
<div class="space-y-4">
    <a href="{{ route('account.support.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-900">
        <x-icon name="arrow-left" class="h-4 w-4" /> {{ __('support.back') }}
    </a>

    @include('partials.support-chat', ['ticket' => $ticket, 'isAdmin' => false])
</div>
@endsection
