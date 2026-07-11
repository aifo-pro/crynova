@extends('layouts.app')
@section('title', __('support.title'))

@section('content')
<div class="space-y-6" x-data="{ open: {{ $tickets->isEmpty() ? 'true' : 'false' }} }">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-950">{{ __('support.title') }}</h1>
            <p class="mt-1 text-slate-500">{{ __('support.subtitle') }}</p>
        </div>
        <button @click="open = !open" class="inline-flex items-center justify-center gap-2 rounded-full bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700">
            <x-icon name="plus" class="h-4 w-4" /> {{ __('support.new') }}
        </button>
    </div>

    {{-- New ticket form --}}
    <div x-show="open" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 font-semibold text-slate-950">{{ __('support.new') }}</h2>
        <form method="POST" action="{{ route('account.support.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="fin-label">{{ __('support.subject') }}</label>
                <input name="subject" value="{{ old('subject') }}" required maxlength="160" class="fin-input @error('subject') border-rose-400 @enderror" placeholder="{{ __('support.subject_ph') }}">
                @error('subject')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            @if($departments->isNotEmpty())
                <div>
                    <label class="fin-label">{{ __('support.department') }}</label>
                    <select name="department_id" class="fin-input @error('department_id') border-rose-400 @enderror" required>
                        <option value="">{{ __('support.department_ph') }}</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
            @endif
            <div>
                <label class="fin-label">{{ __('support.message') }}</label>
                <textarea name="body" required rows="5" class="fin-input @error('body') border-rose-400 @enderror" placeholder="{{ __('support.message_ph') }}">{{ old('body') }}</textarea>
                @error('body')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="fin-label">{{ __('support.attach') }}</label>
                <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.zip,.txt,.doc,.docx,.xls,.xlsx" class="fin-input">
                <p class="mt-1 text-xs text-slate-400">{{ __('support.attach_hint') }}</p>
                @error('files.*')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <x-button type="submit" icon="arrow-right">{{ __('support.send') }}</x-button>
        </form>
    </div>

    {{-- Tickets list --}}
    @if($tickets->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-400">{{ __('support.empty') }}</div>
    @else
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            @foreach($tickets as $ticket)
                @php
                    $badge = match($ticket->status) {
                        'closed'   => ['bg-slate-100 text-slate-500', __('support.status.closed')],
                        'answered' => ['bg-emerald-50 text-emerald-600', __('support.status.answered')],
                        default    => ['bg-amber-50 text-amber-600', __('support.status.open')],
                    };
                @endphp
                <a href="{{ route('account.support.show', $ticket) }}" class="flex items-center gap-4 border-b border-slate-100 px-5 py-4 transition last:border-0 hover:bg-slate-50">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-blue-50 text-blue-600"><x-icon name="message-circle" class="h-5 w-5" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="flex items-center gap-2 truncate font-semibold text-slate-950">
                            {{ $ticket->subject }}
                            @if($ticket->user_unread)<span class="h-2 w-2 shrink-0 rounded-full bg-blue-600"></span>@endif
                        </p>
                        <p class="text-xs text-slate-400">#{{ $ticket->id }} · {{ optional($ticket->last_message_at)->diffForHumans() }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $badge[0] }}">{{ $badge[1] }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
