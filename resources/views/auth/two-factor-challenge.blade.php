@extends('layouts.app')

@section('title', 'Two-Factor Challenge')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Two-Factor Authentication
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Enter the code from your authenticator app.
            </p>
        </div>

        <form method="POST" action="{{ route('two-factor.login') }}" class="mt-8 space-y-6">
            @csrf
            <div>
                <label for="code" class="sr-only">Authentication Code</label>
                <input id="code" name="code" type="text" autocomplete="one-time-code" required
                       class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm"
                       placeholder="Enter your authenticator code">
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    Verify Code
                </button>
            </div>
        </form>

        <div class="text-center">
            <p class="text-sm text-gray-600">Lost your device?</p>
            <form method="POST" action="{{ route('two-factor.login') }}" class="mt-2">
                @csrf
                <button type="submit" name="recovery_code" value="1" class="text-sm text-primary-600 hover:text-primary-500">
                    Use a recovery code
                </button>
            </form>
        </div>
    </div>
</div>
@endsection