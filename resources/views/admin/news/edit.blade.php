@extends('layouts.app')
@section('title', 'Редагувати новину')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.news.index') }}" class="text-slate-400 hover:text-slate-900"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-slate-950">{{ $item->title }}</h1>
    </div>
    <form method="POST" action="{{ route('admin.news.update', $item) }}">
        @csrf @method('PATCH')
        @include('admin.news._form', ['item' => $item])
    </form>
</div>
@endsection
