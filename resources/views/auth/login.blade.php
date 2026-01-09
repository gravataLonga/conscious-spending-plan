@extends('layouts.auth')

@section('content')
    <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-8 shadow-sm">
        <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Welcome back</p>
            <h1 class="text-3xl font-semibold text-slate-900 font-['Fraunces',serif]">Log in to your plan</h1>
            <p class="text-sm text-slate-600">Keep your spending plan synced and up to date.</p>
        </div>

        <div class="mt-6 space-y-4">
            @include('partials.form-errors')

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <form class="space-y-4" method="POST" action="{{ route('login') }}">
                @csrf

                <label class="block text-sm font-medium text-slate-700">
                    Email
                    <input
                        class="input-field mt-2"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                    />
                </label>

                <label class="block text-sm font-medium text-slate-700">
                    Password
                    <input
                        class="input-field mt-2"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    />
                </label>

                <div class="flex items-center justify-between text-sm text-slate-600">
                    <label class="flex items-center gap-2">
                        <input class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400" type="checkbox" name="remember" />
                        Remember me
                    </label>
                    <a class="font-semibold text-slate-700 transition hover:text-slate-900" href="{{ route('password.request') }}">Forgot password?</a>
                </div>

                <button class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" type="submit">
                    Log in
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-slate-600">
            New here?
            <a class="font-semibold text-slate-800 transition hover:text-slate-950" href="{{ route('register') }}">Create an account</a>
        </p>
    </div>
@endsection
