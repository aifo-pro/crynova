<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $siteName }} — Maintenance</title>
    <link rel="icon" href="{{ asset('assets/crynova/favicon/favicon.ico') }}" sizes="any">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <section class="w-full max-w-xl rounded-[2rem] border border-slate-200 bg-white p-8 text-center shadow-xl shadow-slate-200/70">
            <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-blue-50 text-blue-700">
                <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.7 6.3 2-2a2.1 2.1 0 1 1 3 3l-2 2m-3-3 3 3m-11 8.4-2 2a2.1 2.1 0 1 1-3-3l2-2m3 3-3-3M12 8l4 4m-8 0 4 4"/>
                </svg>
            </div>
            <h1 class="mt-6 text-3xl font-black tracking-[-0.03em] text-slate-950">{{ $siteName }} тимчасово на обслуговуванні</h1>
            <p class="mt-4 text-base leading-7 text-slate-600">{{ $message }}</p>
            <a href="{{ route('login') }}" class="mt-7 inline-flex items-center justify-center rounded-full bg-blue-600 px-7 py-3 text-sm font-bold text-white hover:bg-blue-700">
                Вхід для адміністратора
            </a>
        </section>
    </main>
</body>
</html>
