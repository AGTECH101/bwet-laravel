@extends('layouts.app')

@section('title', 'Page Not Found - ' . config('app.name'))

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 px-4">
    <div class="text-center max-w-md">
        <h1 class="text-9xl font-bold text-gray-200 drop-shadow-lg">404</h1>
        <h2 class="text-3xl font-bold text-gray-900 -mt-8">Page Not Found</h2>
        <p class="text-gray-600 text-lg mb-8">Sorry! The page you're looking for doesn't exist or has been moved.</p>

        @auth
            <a href="{{ route('dashboard') }}" class="inline-block px-8 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 shadow-md hover:shadow-lg transition-all">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        @else
            <a href="{{ route('home') }}" class="inline-block px-8 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 shadow-md hover:shadow-lg transition-all">
                <i class="fas fa-home mr-2"></i> Go to Homepage
            </a>
        @endauth

        <div class="mt-8 bg-white rounded-lg shadow-md p-8 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Quick Navigation</h3>
            <div class="space-y-3 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-home w-5 text-primary-600"></i>
                        <span class="ml-3">Dashboard</span>
                        <i class="fas fa-chevron-right ml-auto text-gray-400"></i>
                    </a>
                    <a href="{{ route('poultry.batches.index') }}" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-layer-group w-5 text-blue-600"></i>
                        <span class="ml-3">Batches</span>
                        <i class="fas fa-chevron-right ml-auto text-gray-400"></i>
                    </a>
                    <a href="{{ route('poultry.forms.hub') }}" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-file-alt w-5 text-green-600"></i>
                        <span class="ml-3">Form Hub</span>
                        <i class="fas fa-chevron-right ml-auto text-gray-400"></i>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-sign-in-alt w-5 text-primary-600"></i>
                        <span class="ml-3">Sign In</span>
                        <i class="fas fa-chevron-right ml-auto text-gray-400"></i>
                    </a>
                    <a href="{{ route('register') }}" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-user-plus w-5 text-green-600"></i>
                        <span class="ml-3">Create Account</span>
                        <i class="fas fa-chevron-right ml-auto text-gray-400"></i>
                    </a>
                @endauth
            </div>
        </div>
    </div>
</div>
@endsection