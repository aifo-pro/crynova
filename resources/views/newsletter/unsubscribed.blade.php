@extends('layouts.app')
@section('title', 'Відписка від розсилки')

@section('content')
<div class="mx-auto flex min-h-[70vh] max-w-lg items-center px-4">
    <div class="w-full rounded-[2rem] border border-slate-200 bg-white p-8 text-center shadow-xl shadow-slate-200/70">
        <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-50 text-emerald-700">
            <x-icon name="check" class="h-6 w-6" />
        </div>
        <h1 class="mt-6 text-2xl font-black text-slate-950">Ви відписались</h1>
        <p class="mt-3 text-sm leading-6 text-slate-500">{{ $email }} більше не отримуватиме масові email-розсилки Crynova.</p>
        <x-button href="{{ route('home') }}" class="mt-6 rounded-full px-8">На головну</x-button>
    </div>
</div>
@endsection
