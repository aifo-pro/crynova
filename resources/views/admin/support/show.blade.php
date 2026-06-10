@extends('layouts.app')
@section('title', 'Тікет #'.$ticket->id)

@section('content')
<div class="space-y-4">
    <a href="{{ route('admin.support.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-900">
        <x-icon name="arrow-left" class="h-4 w-4" /> До тікетів
    </a>

    @include('partials.support-chat', ['ticket' => $ticket, 'isAdmin' => true])
</div>
@endsection
