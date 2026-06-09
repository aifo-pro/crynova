@extends('layouts.app')
@section('title', 'Terms')
@section('content')
<section class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <x-card title="Terms of Service" subtitle="Placeholder legal copy for Crynova. Replace with counsel-approved terms before production.">
        <div class="prose prose-invert max-w-none text-slate-300">
            <p>Crynova provides crypto payment processing tools for merchants, including hosted checkout pages, invoice APIs, webhook notifications and operational dashboards.</p>
            <p>Merchants are responsible for lawful use of the service, accurate product information, tax obligations, sanctions screening and compliance with applicable regulations.</p>
            <p>Crypto transactions may be irreversible. Crynova may suspend accounts, decline transactions, request information or limit withdrawals where risk, compliance or security controls require it.</p>
        </div>
    </x-card>
</section>
@endsection
