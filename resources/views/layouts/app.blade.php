<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Conscious Spending Plan')</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700&family=fraunces:600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900">
        <div class="relative overflow-hidden">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                <div class="absolute -top-32 right-[-10%] h-72 w-72 rounded-xl bg-[radial-gradient(circle,rgba(59,130,246,0.18)_0%,rgba(59,130,246,0)_70%)] blur-2xl"></div>
                <div class="absolute left-[-15%] top-40 h-80 w-80 rounded-xl bg-[radial-gradient(circle,rgba(15,23,42,0.14)_0%,rgba(15,23,42,0)_72%)] blur-2xl"></div>
                <div class="absolute bottom-[-18%] right-10 h-72 w-72 rounded-xl bg-[radial-gradient(circle,rgba(148,163,184,0.18)_0%,rgba(148,163,184,0)_70%)] blur-2xl"></div>
            </div>

            <header class="w-full border border-slate-200/70 bg-white/80 shadow-sm">
                <div class="flex w-full flex-wrap items-center justify-between gap-4 px-4 py-3 text-sm font-semibold text-slate-600 md:px-8">
                    <div class="flex items-center gap-4">
                        <a class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-700" href="{{ route('plan.show') }}">
                            Conscious Spending Plan
                            <span class="inline-flex h-1.5 w-1.5 rounded-sm bg-slate-500"></span>
                        </a>
                        <nav class="flex flex-wrap items-center gap-4 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                            <a class="text-slate-600 transition hover:text-slate-900" href="{{ route('plan.show') }}">Plans</a>
                            <a class="text-slate-600 transition hover:text-slate-900" href="{{ route('plan.snapshots.summary') }}">Snapshots</a>
                        </nav>
                    </div>
                    <nav class="flex flex-wrap items-center gap-4 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                        <a class="text-slate-600 transition hover:text-slate-900" href="{{ route('account.edit') }}">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="text-slate-600 transition hover:text-slate-900" type="submit">Logout</button>
                        </form>
                    </nav>
                </div>
            </header>

            <main>
                @yield('content')
            </main>
        </div>
    </body>
</html>
