<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Conscious Spending Plan</title>

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
                <header class="flex flex-wrap items-center justify-between gap-4">
                    <div class="inline-flex items-center gap-2 rounded-md border border-slate-200/80 bg-white/80 px-4 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-700">
                        Conscious Spending Plan
                        <span class="inline-flex h-1.5 w-1.5 rounded-sm bg-slate-500"></span>
                    </div>
                    <div class="flex items-center gap-3 text-sm font-semibold">
                        @auth
                            <a class="text-slate-600 transition hover:text-slate-900" href="{{ route('account.edit') }}">Account</a>
                            <a class="rounded-md bg-slate-900 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-white transition hover:bg-slate-800" href="{{ url('/plan') }}">
                                View plan
                            </a>
                        @else
                            <a class="text-slate-600 transition hover:text-slate-900" href="{{ route('login') }}">Login</a>
                            <a class="rounded-md bg-slate-900 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-white transition hover:bg-slate-800" href="{{ route('register') }}">
                                Register
                            </a>
                        @endauth
                    </div>
                </header>

                <section class="mt-16 grid gap-10 lg:grid-cols-[1.1fr,0.9fr]">
                    <div class="space-y-6 animate-[rise_0.8s_ease-out]">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Plan with intention</p>
                        <h1 class="text-4xl font-semibold leading-tight text-slate-950 md:text-5xl lg:text-6xl font-['Fraunces',serif]">
                            A shared money plan that keeps spending honest and goals visible.
                        </h1>
                        <p class="text-base text-slate-700 md:text-lg">
                            The Conscious Spending Plan turns income, expenses, investing, and savings into a clear, side-by-side view for every partner. Update it together, and see what is truly guilt-free.
                        </p>
                        <div class="flex flex-wrap items-center gap-4">
                            @auth
                                <a class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" href="{{ url('/plan') }}">
                                    Open your plan
                                </a>
                            @else
                                <a class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" href="{{ route('register') }}">
                                    Create your plan
                                </a>
                                <a class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-5 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900" href="{{ route('login') }}">
                                    Sign in
                                </a>
                            @endauth
                        </div>
                        <div class="flex flex-wrap gap-6 text-sm font-semibold text-slate-600">
                            <span>Track net + gross income</span>
                            <span>Auto buffer on expenses</span>
                            <span>Guilt-free spending clarity</span>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-6 shadow-sm">
                        <div class="flex flex-col gap-6">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900">What you get</h2>
                                <p class="text-sm text-slate-600">A complete snapshot of your conscious spending plan.</p>
                            </div>
                            <div class="grid gap-4">
                                <div class="rounded-lg border border-slate-200/70 bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Net worth</p>
                                    <p class="mt-2 text-sm text-slate-700">Assets, invested, savings, and debt in one place.</p>
                                </div>
                                <div class="rounded-lg border border-slate-200/70 bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Income + expenses</p>
                                    <p class="mt-2 text-sm text-slate-700">Category totals plus buffer % to keep you safe.</p>
                                </div>
                                <div class="rounded-lg border border-slate-200/70 bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Saving goals</p>
                                    <p class="mt-2 text-sm text-slate-700">Vacation, gifts, emergency fund—always visible.</p>
                                </div>
                            </div>
                            <div class="rounded-lg border border-slate-200/70 bg-white px-4 py-4">
                                <p class="text-sm font-semibold text-slate-800">Built for couples and solo planners alike.</p>
                                <p class="mt-1 text-sm text-slate-600">Add or remove partners and keep a shared view of progress.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mt-16 grid gap-6 md:grid-cols-3">
                    <div class="rounded-xl border border-slate-200/70 bg-white/80 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">1. Set income</p>
                        <p class="mt-3 text-base font-semibold text-slate-900">Capture net + gross earnings per partner.</p>
                        <p class="mt-2 text-sm text-slate-600">Instant totals show your yearly income picture.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200/70 bg-white/80 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">2. Plan expenses</p>
                        <p class="mt-3 text-base font-semibold text-slate-900">Allocate essentials and see the buffer update.</p>
                        <p class="mt-2 text-sm text-slate-600">Keep spending realistic without endless spreadsheets.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200/70 bg-white/80 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">3. Save + invest</p>
                        <p class="mt-3 text-base font-semibold text-slate-900">Track goals and the guilt-free remainder.</p>
                        <p class="mt-2 text-sm text-slate-600">Know exactly what is left for fun spending.</p>
                    </div>
                </section>

                <section class="mt-16 rounded-2xl border border-slate-200/80 bg-white/90 p-8 shadow-sm">
                    <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Ready to start?</p>
                            <h2 class="mt-3 text-3xl font-semibold text-slate-900 font-['Fraunces',serif]">Build your conscious spending plan today.</h2>
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            @auth
                                <a class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" href="{{ url('/plan') }}">
                                    Go to your plan
                                </a>
                            @else
                                <a class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" href="{{ route('register') }}">
                                    Create free account
                                </a>
                                <a class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-5 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900" href="{{ route('login') }}">
                                    Sign in
                                </a>
                            @endauth
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
