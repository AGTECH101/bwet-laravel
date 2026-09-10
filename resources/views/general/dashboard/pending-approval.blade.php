@extends('layouts.app')

@section('title', 'Pending Approval - ' . config('app.name'))

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 px-4">
    <div class="max-w-md w-full text-center">
        <div class="mb-8">
            <div class="w-24 h-24 mx-auto rounded-full bg-yellow-100 flex items-center justify-center">
                <i class="fas fa-hourglass-half text-yellow-600 text-4xl"></i>
            </div>
        </div>

        <h1 class="text-3xl font-bold text-gray-900 mb-4">Pending Approval</h1>
        <p class="text-lg text-gray-600 mb-6">
            Your account is pending approval from the system administrator.
        </p>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-left">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">What happens next?</h3>
            <ul class="space-y-3 text-sm">
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mt-0.5 mr-3"></i>
                    <div>
                        <span class="font-medium text-gray-900">Registration received</span>
                        <p class="text-gray-500">Your registration has been submitted and is under review.</p>
                    </div>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-user-tie text-blue-500 mt-0.5 mr-3"></i>
                    <div>
                        <span class="font-medium text-gray-900">Administrator review</span>
                        <p class="text-gray-500">An admin will review your account details and approve access.</p>
                    </div>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-bell text-yellow-500 mt-0.5 mr-3"></i>
                    <div>
                        <span class="font-medium text-gray-900">You will be notified</span>
                        <p class="text-gray-500">Once approved, you can log in and access the system.</p>
                    </div>
                </li>
            </ul>
        </div>

        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-600">
                <span class="font-medium">Role:</span> {{ ucfirst(auth()->user()->role) }}<br>
                <span class="font-medium">Registered:</span> {{ auth()->user()->created_at->format('M d, Y') }}
            </p>
        </div>

        <div class="mt-8">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </button>
            </form>
        </div>
    </div>
</div>
@endsection