@extends('layouts.auth')

@section('content')
    <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-8 shadow-sm">
        <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Security check</p>
            <h1 class="text-3xl font-semibold text-slate-900 font-['Fraunces',serif]">Confirm your password</h1>
            <p class="text-sm text-slate-600">Please confirm your password before continuing.</p>
        </div>

        <div class="mt-6 space-y-4">
            @include('partials.form-errors')

            <form class="space-y-4" method="POST" action="{{ route('password.confirm.store') }}">
                @csrf

                <label class="block text-sm font-medium text-slate-700">
                    Password
                    <input
                        class="input-field mt-2"
                        type="password"
                        name="password"
                        required
                        autofocus
                        autocomplete="current-password"
                    />
                </label>

                <button class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" type="submit">
                    Confirm password
                </button>
            </form>
        </div>
    </div>
@endsection
