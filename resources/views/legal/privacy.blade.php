@extends('layouts.app')
@section('title', 'Privacy Policy')
@section('content')
<section class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <x-card title="Privacy Policy" subtitle="Placeholder privacy copy. Replace with a production policy before launch.">
        <div class="space-y-4 text-slate-300">
            <p>We process account, merchant, transaction, security and technical data to provide payment services, prevent abuse and meet compliance obligations.</p>
            <p>Access to sensitive data is limited to authorized operational needs. Secrets and authentication factors should be protected using encryption, hashing and audit controls.</p>
            <p>Merchants should disclose their own customer data practices when embedding or linking to Crynova checkout.</p>
        </div>
    </x-card>
</section>
@endsection
