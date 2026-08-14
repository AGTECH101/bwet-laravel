@extends('layouts.app')

@section('title', 'Select Sector - ' . config('app.name'))

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-green-50 to-gray-100 px-4 py-12">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900">Welcome to {{ config('app.name') }}</h1>
        <p class="text-lg text-gray-600 mt-2">Select a sector to continue</p>
    </div>

    <div class="max-w-6xl w-full mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($sectors as $sector)
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-shadow duration-300 overflow-hidden">
                    <div class="p-6 text-center">
                        <div class="w-20 h-20 mx-auto rounded-full bg-primary-100 flex items-center justify-center mb-4">
                            <i class="fas fa-{{ $sector->icon ?? 'building' }} text-primary-600 text-3xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">{{ $sector->name }}</h2>
                        <p class="text-sm text-gray-500 mt-2">{{ $sector->description ?: 'Manage your ' . $sector->name . ' operations' }}</p>
                        <div class="mt-6">
                            <form method="POST" action="{{ route('sectors.select') }}">
                                @csrf
                                <input type="hidden" name="sector_id" value="{{ $sector->id }}">
                                <button type="submit" class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors">
                                    Select {{ $sector->name }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-500">No sectors are currently available.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-12 text-center">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-sign-out-alt mr-1"></i> Logout
            </button>
        </form>
    </div>
</div>
@endsection