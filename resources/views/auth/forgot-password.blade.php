@extends('layouts.auth')

@section('content')
    <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-8 shadow-sm">
        <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Reset access</p>
            <h1 class="text-3xl font-semibold text-slate-900 font-['Fraunces',serif]">Forgot your password?</h1>
            <p class="text-sm text-slate-600">Send a reset link to the email associated with your account.</p>
        </div>

        <div class="mt-6 space-y-4">
            @include('partials.form-errors')

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <form class="space-y-4" method="POST" action="{{ route('password.email') }}">
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

                <button class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" type="submit">
                    Email reset link
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-slate-600">
            Remembered it?
            <a class="font-semibold text-slate-800 transition hover:text-slate-950" href="{{ route('login') }}">Back to login</a>
        </p>
    </div>
@endsection
