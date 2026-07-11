@extends('layouts.app')
@section('title', 'Новий шаблон')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.templates.index') }}" class="text-slate-400 hover:text-blue-600"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-slate-950">Новий шаблон відповіді</h1>
    </div>
    <form method="POST" action="{{ route('admin.templates.store') }}">
        @csrf
        @include('admin.templates._form')
    </form>
</div>
@endsection
