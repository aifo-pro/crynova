@extends('layouts.app')
@section('title', __('public.contact.title'))
@section('meta_description', __('public.contact.subtitle'))

@section('content')
<section class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8">
    <div>
        <span class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-blue-700">{{ __('public.contact.badge') }}</span>
        <h1 class="mt-5 text-4xl font-black tracking-[-0.02em] text-slate-950 sm:text-5xl">{{ __('public.contact.title') }}</h1>
        <p class="mt-5 text-lg leading-8 text-slate-600">{{ __('public.contact.subtitle') }}</p>

        <div class="mt-8 space-y-3">
            <a href="mailto:sales@crynova.io" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow-sm transition hover:border-blue-200 hover:shadow-md">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-600"><x-icon name="message-circle" class="h-5 w-5" /></span>
                <span><span class="block font-bold text-slate-950">{{ __('public.contact.sales') }}</span><span class="text-slate-500">sales@crynova.io</span></span>
            </a>
            <a href="mailto:support@crynova.io" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow-sm transition hover:border-blue-200 hover:shadow-md">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-600"><x-icon name="shield" class="h-5 w-5" /></span>
                <span><span class="block font-bold text-slate-950">{{ __('public.contact.support') }}</span><span class="text-slate-500">support@crynova.io</span></span>
            </a>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow-sm">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-amber-50 text-amber-600"><x-icon name="clock" class="h-5 w-5" /></span>
                <span><span class="block font-bold text-slate-950">{{ __('public.contact.response') }}</span><span class="text-slate-500">{{ __('public.contact.response_value') }}</span></span>
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60 sm:p-8">
        <h2 class="text-lg font-black text-slate-950">{{ __('public.contact.form_title') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('public.contact.form_subtitle') }}</p>

        @if(session('success'))
            <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('contact.store') }}" class="mt-6 grid gap-4 sm:grid-cols-2">
            @csrf
            <div>
                <label class="fin-label">{{ __('public.contact.name') }}</label>
                <input name="name" value="{{ old('name') }}" required class="fin-input" placeholder="{{ __('public.contact.name_ph') }}">
            </div>
            <div>
                <label class="fin-label">{{ __('public.contact.email') }}</label>
                <input name="email" type="email" value="{{ old('email') }}" required class="fin-input" placeholder="alex@example.com">
            </div>
            <div class="sm:col-span-2">
                <label class="fin-label">{{ __('public.contact.subject') }}</label>
                <input name="subject" value="{{ old('subject') }}" required class="fin-input" placeholder="{{ __('public.contact.subject_ph') }}">
            </div>
            <div class="sm:col-span-2">
                <label class="fin-label">{{ __('public.contact.message') }}</label>
                <textarea name="message" required class="fin-input" rows="6" placeholder="{{ __('public.contact.message_ph') }}">{{ old('message') }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <x-recaptcha-v3 action="contact" />
            </div>
            <x-button type="submit" class="w-full sm:col-span-2">{{ __('public.contact.send') }}</x-button>
        </form>
    </div>
</section>
@endsection
