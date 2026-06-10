@extends('layouts.app')
@section('title', 'Нова новина')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.news.index') }}" class="text-slate-400 hover:text-slate-900"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-slate-950">Нова новина</h1>
    </div>
    <form method="POST" action="{{ route('admin.news.store') }}">
        @csrf
        @include('admin.news._form', ['item' => null])
    </form>
</div>
@endsection
