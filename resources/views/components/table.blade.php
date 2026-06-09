@props(['headers' => []])
<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/72']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            @if(count($headers))
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500 dark:border-slate-800 dark:bg-slate-900/70">
                    <tr>
                        @foreach($headers as $header)
                            <th class="px-4 py-3 font-semibold">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody class="divide-y divide-slate-100 text-slate-700 dark:divide-slate-800 dark:text-slate-200">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
