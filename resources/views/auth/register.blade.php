@extends('layouts.auth')

@section('content')
    <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-8 shadow-sm">
        <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Get started</p>
            <h1 class="text-3xl font-semibold text-slate-900 font-['Fraunces',serif]">Create your account</h1>
            <p class="text-sm text-slate-600">Track and update your conscious spending plan anytime.</p>
        </div>

        <div class="mt-6 space-y-4">
            @include('partials.form-errors')

            <form class="space-y-4" method="POST" action="{{ route('register') }}">
                @csrf

                <label class="block text-sm font-medium text-slate-700">
                    Name
                    <input
                        class="input-field mt-2"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        autocomplete="name"
                    />
                </label>

                <label class="block text-sm font-medium text-slate-700">
                    Email
                    <input
                        class="input-field mt-2"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
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
                        autocomplete="new-password"
                    />
                </label>

                <label class="block text-sm font-medium text-slate-700">
                    Confirm password
                    <input
                        class="input-field mt-2"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                </label>

                <button class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" type="submit">
                    Create account
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-slate-600">
            Already have an account?
            <a class="font-semibold text-slate-800 transition hover:text-slate-950" href="{{ route('login') }}">Log in</a>
        </p>
    </div>
@endsection
