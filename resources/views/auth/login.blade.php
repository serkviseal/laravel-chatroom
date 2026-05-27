@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-950">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">💬</div>
            <h1 class="text-2xl font-bold text-white">Welcome back</h1>
            <p class="text-gray-400 text-sm mt-1">Sign in to join the conversation</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            @if ($errors->any())
            <div class="bg-red-900/40 border border-red-700 text-red-300 text-sm rounded-lg px-4 py-3">
                {{ $errors->first() }}
            </div>
            @endif

            <div>
                <label class="block text-sm text-gray-400 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2.5 text-white placeholder-gray-500
                           focus:outline-none focus:border-brand-500 text-sm" />
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-1">Password</label>
                <input type="password" name="password" required
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2.5 text-white placeholder-gray-500
                           focus:outline-none focus:border-brand-500 text-sm" />
            </div>

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2 text-gray-400 cursor-pointer">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}
                        class="rounded border-gray-600 bg-gray-800 text-brand-500 focus:ring-brand-500" />
                    Remember me
                </label>
                @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-brand-400 hover:text-brand-300">Forgot password?</a>
                @endif
            </div>

            <button type="submit"
                class="w-full bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 rounded-lg text-sm transition-colors">
                Sign in
            </button>
        </form>

        @if (Route::has('register'))
        <p class="text-center text-sm text-gray-500 mt-6">
            Don't have an account?
            <a href="{{ route('register') }}" class="text-brand-400 hover:text-brand-300">Create one</a>
        </p>
        @endif
    </div>
</div>
@endsection
