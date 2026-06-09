@extends('layouts.app')
@section('title', 'Contact')
@section('meta_description', 'Зв'яжіться з командою Crynova: питання щодо інтеграції, тарифів та партнерства у прийманні криптоплатежів.')

@section('content')
<section class="mx-auto grid max-w-7xl gap-8 px-4 py-14 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8">
    <div>
        <x-badge variant="teal">Contact</x-badge>
        <h1 class="mt-5 text-4xl font-semibold text-white sm:text-5xl">Talk to Crynova</h1>
        <p class="mt-5 text-lg leading-8 text-slate-300">For merchant onboarding, API integration, custom limits or compliance review, send a message to the Crynova team.</p>
        <div class="mt-8 space-y-3 text-sm text-slate-300">
            <div class="rounded-lg border border-slate-800 bg-slate-950/72 p-4">Sales: sales@crynova.io</div>
            <div class="rounded-lg border border-slate-800 bg-slate-950/72 p-4">Support: support@crynova.io</div>
            <div class="rounded-lg border border-slate-800 bg-slate-950/72 p-4">Response target: within one business day</div>
        </div>
    </div>

    <x-card title="Send a message" subtitle="Our team will route your request to support, sales or integration engineering.">
        <form method="POST" action="{{ route('contact.store') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            <div>
                <label class="fin-label">Name</label>
                <input name="name" value="{{ old('name') }}" required class="fin-input" placeholder="Alex Smith">
            </div>
            <div>
                <label class="fin-label">Work email</label>
                <input name="email" type="email" value="{{ old('email') }}" required class="fin-input" placeholder="alex@example.com">
            </div>
            <div class="sm:col-span-2">
                <label class="fin-label">Subject</label>
                <input name="subject" value="{{ old('subject') }}" required class="fin-input" placeholder="Crypto payments for my platform">
            </div>
            <div class="sm:col-span-2">
                <label class="fin-label">Message</label>
                <textarea name="message" required class="fin-input" rows="6" placeholder="Tell us about your volume, currencies and integration timeline.">{{ old('message') }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <x-recaptcha-v3 action="contact" />
            </div>
            <x-button type="submit" class="sm:col-span-2">Send message</x-button>
        </form>
    </x-card>
</section>
@endsection
