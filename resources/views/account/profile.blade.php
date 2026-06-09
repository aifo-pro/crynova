@extends('layouts.app')
@section('title', 'Profile')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <h1 class="text-3xl font-semibold text-slate-950">Profile</h1>
        <p class="mt-1 text-slate-500">Your personal account details.</p>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="fin-label">Your name</label>
                <input name="name" type="text" class="fin-input @error('name') border-rose-500 @enderror"
                       value="{{ old('name', $user->name) }}" required>
                @error('name')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="fin-label">Email</label>
                <input type="email" class="fin-input opacity-60 cursor-not-allowed" value="{{ $user->email }}" disabled>
                <p class="mt-1 text-xs text-slate-400">Contact support to change your email.</p>
            </div>
            <x-button type="submit" icon="save">Save profile</x-button>
        </form>
    </div>
</div>
@endsection
