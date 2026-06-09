@extends('layouts.app')
@section('title', 'Create Invoice')

@section('content')
<div class="grid gap-6 xl:grid-cols-[1fr_0.75fr]">
    <x-card title="Create invoice" subtitle="Generate a hosted payment page for your customer.">
        <form method="POST" action="{{ route('merchant.invoices.store') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            <div>
                <label class="fin-label" for="amount">Amount</label>
                <input id="amount" name="amount" type="number" step="any" min="0"
                       class="fin-input @error('amount') border-rose-500 @enderror"
                       placeholder="149.00" value="{{ old('amount') }}" required>
                @error('amount')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="fin-label" for="currency_id">Currency</label>
                <select id="currency_id" name="currency_id"
                        class="fin-input @error('currency_id') border-rose-500 @enderror" required>
                    <option value="">— choose —</option>
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}" @selected(old('currency_id') == $currency->id)>
                            {{ $currency->code }} — {{ $currency->name }}
                        </option>
                    @endforeach
                </select>
                @error('currency_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="fin-label" for="order_id">Order ID <span class="text-slate-500">(optional)</span></label>
                <input id="order_id" name="order_id" type="text"
                       class="fin-input @error('order_id') border-rose-500 @enderror"
                       placeholder="ORD-1048" value="{{ old('order_id') }}">
                @error('order_id')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="fin-label" for="expires_in">Expires in</label>
                <select id="expires_in" name="expires_in" class="fin-input">
                    @foreach([15 => '15 minutes', 30 => '30 minutes', 60 => '1 hour', 120 => '2 hours', 1440 => '24 hours'] as $mins => $label)
                        <option value="{{ $mins }}" @selected(old('expires_in', 30) == $mins)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="fin-label" for="description">Description <span class="text-slate-500">(optional)</span></label>
                <textarea id="description" name="description" rows="3"
                          class="fin-input @error('description') border-rose-500 @enderror"
                          placeholder="Payment for order ORD-1048">{{ old('description') }}</textarea>
                @error('description')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label class="fin-label" for="metadata">Metadata <span class="text-slate-500">(JSON, optional)</span></label>
                <textarea id="metadata" name="metadata" rows="2"
                          class="fin-input font-mono text-xs @error('metadata') border-rose-500 @enderror"
                          placeholder='{"user_id": 42, "plan": "pro"}'>{{ old('metadata') }}</textarea>
                @error('metadata')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-wrap gap-3 sm:col-span-2">
                <x-button type="submit" icon="credit-card">Create invoice</x-button>
                <x-button href="{{ route('merchant.invoices.index') }}" variant="ghost">Cancel</x-button>
            </div>
        </form>
    </x-card>

    <div class="space-y-6">
        <x-card title="How it works">
            <ol class="space-y-3 text-sm text-slate-400">
                <li class="flex gap-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-500/20 text-xs font-semibold text-teal-200">1</span>
                    Invoice is created with a unique deposit address.
                </li>
                <li class="flex gap-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-500/20 text-xs font-semibold text-teal-200">2</span>
                    Customer pays on the hosted checkout page (QR + address).
                </li>
                <li class="flex gap-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-500/20 text-xs font-semibold text-teal-200">3</span>
                    Blockchain is polled; status updates automatically.
                </li>
                <li class="flex gap-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-500/20 text-xs font-semibold text-teal-200">4</span>
                    Webhook fired on paid/underpaid/overpaid/expired events.
                </li>
            </ol>
        </x-card>

        <x-card title="Checkout preview">
            <div class="rounded-lg border border-slate-800 bg-slate-900/60 p-5 text-center">
                <div class="mx-auto h-32 w-32 overflow-hidden rounded-lg bg-white p-2">
                    {{-- placeholder QR pattern --}}
                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" class="h-full w-full">
                        <rect width="100" height="100" fill="white"/>
                        @foreach([[5,5],[35,5],[65,5],[5,35],[65,35],[5,65],[35,65],[65,65]] as [$x,$y])
                        <rect x="{{ $x }}" y="{{ $y }}" width="25" height="25" rx="2" fill="#0f172a"/>
                        @endforeach
                        <rect x="10" y="10" width="15" height="15" fill="white"/>
                        <rect x="40" y="10" width="5" height="5" fill="#0f172a"/>
                        <rect x="70" y="10" width="15" height="15" fill="white"/>
                        <rect x="10" y="70" width="15" height="15" fill="white"/>
                    </svg>
                </div>
                <p class="mt-4 text-sm text-slate-300">Hosted checkout at</p>
                <p class="font-mono text-xs text-teal-300">/pay/{invoice_uuid}</p>
                <p class="mt-3 text-xs text-slate-500">QR code, countdown timer, and copy address shown to customer.</p>
            </div>
        </x-card>
    </div>
</div>
@endsection
