@extends('layouts.app')
@section('title', 'Редагувати модуль')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.modules.index') }}" class="text-slate-400 hover:text-slate-900"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        <h1 class="text-3xl font-semibold text-slate-950">{{ $module->name }}</h1>
    </div>

    <form method="POST" action="{{ route('admin.modules.update', $module) }}" enctype="multipart/form-data">
        @csrf @method('PATCH')
        @include('admin.modules._form', ['module' => $module])
    </form>
</div>
@endsection
