<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Conscious Spending Plan' }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700&family=fraunces:600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900">
        <div class="relative min-h-screen overflow-hidden">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                <div class="absolute -top-24 right-[-8%] h-72 w-72 rounded-xl bg-[radial-gradient(circle,rgba(59,130,246,0.18)_0%,rgba(59,130,246,0)_70%)] blur-2xl"></div>
                <div class="absolute left-[-12%] top-28 h-80 w-80 rounded-xl bg-[radial-gradient(circle,rgba(15,23,42,0.14)_0%,rgba(15,23,42,0)_72%)] blur-2xl"></div>
                <div class="absolute bottom-[-18%] right-6 h-72 w-72 rounded-xl bg-[radial-gradient(circle,rgba(148,163,184,0.18)_0%,rgba(148,163,184,0)_70%)] blur-2xl"></div>
            </div>

            <main class="relative mx-auto flex min-h-screen max-w-6xl flex-col px-4 py-10 md:py-16">
                <div class="flex items-center justify-between">
                    <a class="inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.2em] text-slate-600" href="{{ url('/') }}">
                        Conscious Spending Plan
                        <span class="inline-flex h-1.5 w-1.5 rounded-sm bg-slate-500"></span>
                    </a>
                    <div class="flex items-center gap-3 text-sm font-semibold">
                        @auth
                            <a class="text-slate-600 transition hover:text-slate-900" href="{{ route('account.edit') }}">Account</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="text-slate-600 transition hover:text-slate-900" type="submit">Logout</button>
                            </form>
                        @else
                            <a class="text-slate-600 transition hover:text-slate-900" href="{{ route('login') }}">Login</a>
                            <a class="rounded-md bg-slate-900 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-white transition hover:bg-slate-800" href="{{ route('register') }}">
                                Register
                            </a>
                        @endauth
                    </div>
                </div>

                <div class="mt-10 flex flex-1 items-center justify-center">
                    <div class="w-full max-w-xl">
                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
