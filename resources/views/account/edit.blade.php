@extends('layouts.auth')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-8 shadow-sm">
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Account settings</p>
                <h1 class="text-3xl font-semibold text-slate-900 font-['Fraunces',serif]">Your profile</h1>
                <p class="text-sm text-slate-600">Update your name and email used for logging in.</p>
            </div>

            <div class="mt-6 space-y-4">
                @if ($errors->updateProfileInformation->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach ($errors->updateProfileInformation->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="space-y-4" method="POST" action="{{ route('user-profile-information.update') }}">
                    @csrf
                    @method('PUT')

                    <label class="block text-sm font-medium text-slate-700">
                        Name
                        <input
                            class="input-field mt-2"
                            type="text"
                            name="name"
                            value="{{ old('name', auth()->user()->name) }}"
                            required
                            autocomplete="name"
                        />
                    </label>

                    <label class="block text-sm font-medium text-slate-700">
                        Email
                        <input
                            class="input-field mt-2"
                            type="email"
                            name="email"
                            value="{{ old('email', auth()->user()->email) }}"
                            required
                            autocomplete="username"
                        />
                    </label>

                    <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" type="submit">
                        Save profile
                    </button>
                </form>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-8 shadow-sm">
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Security</p>
                <h2 class="text-2xl font-semibold text-slate-900 font-['Fraunces',serif]">Update password</h2>
                <p class="text-sm text-slate-600">Choose a strong password and keep it private.</p>
            </div>

            <div class="mt-6 space-y-4">
                @if ($errors->updatePassword->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach ($errors->updatePassword->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="space-y-4" method="POST" action="{{ route('user-password.update') }}">
                    @csrf
                    @method('PUT')

                    <label class="block text-sm font-medium text-slate-700">
                        Current password
                        <input
                            class="input-field mt-2"
                            type="password"
                            name="current_password"
                            required
                            autocomplete="current-password"
                        />
                    </label>

                    <label class="block text-sm font-medium text-slate-700">
                        New password
                        <input
                            class="input-field mt-2"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                        />
                    </label>

                    <label class="block text-sm font-medium text-slate-700">
                        Confirm new password
                        <input
                            class="input-field mt-2"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                        />
                    </label>

                    <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" type="submit">
                        Update password
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
