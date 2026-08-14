@extends('layouts.app')

@section('title', 'My Profile - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">My Profile</h1>
        <p class="text-sm text-gray-600">View and manage your account information</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <a href="{{ route('profile.edit') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            <i class="fas fa-edit mr-2"></i> Edit Profile
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Profile Card -->
    <div class="md:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
            <div class="mb-3">
                <div class="w-24 h-24 rounded-full bg-primary-100 flex items-center justify-center mx-auto text-3xl font-bold text-primary-600">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
            </div>
            <h3 class="text-lg font-semibold text-gray-900">{{ auth()->user()->name }}</h3>
            <div class="flex items-center justify-center mt-2 space-x-2">
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                    {{ auth()->user()->role == 'admin' ? 'bg-red-100 text-red-800' :
                       (auth()->user()->role == 'manager' ? 'bg-blue-100 text-blue-800' :
                       (auth()->user()->role == 'staff' ? 'bg-green-100 text-green-800' :
                       'bg-purple-100 text-purple-800')) }}">
                    {{ ucfirst(auth()->user()->role) }}
                </span>
                @if(auth()->user()->is_approved)
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i> Approved
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-clock mr-1"></i> Pending
                    </span>
                @endif
            </div>
            <p class="text-sm text-gray-500 mt-3">
                <i class="fas fa-calendar-alt mr-1"></i> Member since {{ auth()->user()->created_at->format('M d, Y') }}
            </p>
        </div>

        <!-- Quick Stats (for staff) -->
        @if(auth()->user()->role == 'staff')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
            <h4 class="text-sm font-semibold text-gray-900 mb-4">Activity Summary</h4>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Flock Records</span>
                    <span class="font-bold text-gray-900">{{ $stats['flock_records'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Weight Records</span>
                    <span class="font-bold text-gray-900">{{ $stats['weight_records'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Feed Records</span>
                    <span class="font-bold text-gray-900">{{ $stats['feed_records'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Batches Created</span>
                    <span class="font-bold text-gray-900">{{ $stats['batches_created'] ?? 0 }}</span>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Details -->
    <div class="md:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Account Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                            <dd class="text-sm text-gray-900">{{ auth()->user()->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="text-sm text-gray-900">{{ auth()->user()->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Role</dt>
                            <dd class="text-sm text-gray-900">{{ ucfirst(auth()->user()->role) }}</dd>
                        </div>
                    </dl>
                </div>
                <div>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Phone</dt>
                            <dd class="text-sm text-gray-900">{{ auth()->user()->phone ?? 'Not set' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Farm Location</dt>
                            <dd class="text-sm text-gray-900">{{ auth()->user()->farm_location ?? 'Not set' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Account Status</dt>
                            <dd class="text-sm">
                                @if(auth()->user()->is_approved)
                                    <span class="text-green-600">Approved</span>
                                @else
                                    <span class="text-yellow-600">Pending Approval</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Permissions -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h4 class="text-sm font-medium text-gray-900 mb-4">Permissions</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <i class="fas fa-eye {{ auth()->user()->role != 'investor' ? 'text-green-600' : 'text-gray-400' }} mr-3"></i>
                        <span class="text-sm">View Analytics</span>
                        <span class="ml-auto">
                            @if(auth()->user()->role != 'investor')
                                <i class="fas fa-check text-green-600"></i>
                            @else
                                <i class="fas fa-times text-gray-400"></i>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-edit {{ auth()->user()->role != 'investor' ? 'text-green-600' : 'text-gray-400' }} mr-3"></i>
                        <span class="text-sm">Edit Records</span>
                        <span class="ml-auto">
                            @if(auth()->user()->role != 'investor')
                                <i class="fas fa-check text-green-600"></i>
                            @else
                                <i class="fas fa-times text-gray-400"></i>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-file-export {{ auth()->user()->role == 'admin' || auth()->user()->role == 'manager' ? 'text-green-600' : 'text-gray-400' }} mr-3"></i>
                        <span class="text-sm">Export Data</span>
                        <span class="ml-auto">
                            @if(auth()->user()->role == 'admin' || auth()->user()->role == 'manager')
                                <i class="fas fa-check text-green-600"></i>
                            @else
                                <i class="fas fa-times text-gray-400"></i>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-user-cog {{ auth()->user()->role == 'admin' ? 'text-green-600' : 'text-gray-400' }} mr-3"></i>
                        <span class="text-sm">Manage Users</span>
                        <span class="ml-auto">
                            @if(auth()->user()->role == 'admin')
                                <i class="fas fa-check text-green-600"></i>
                            @else
                                <i class="fas fa-times text-gray-400"></i>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Recent Activity (Staff only) -->
            @if(auth()->user()->role == 'staff')
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h4 class="text-sm font-medium text-gray-900 mb-4">Recent Activity</h4>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Last Flock Record</span>
                        <span class="text-gray-900">{{ $stats['last_flock_date'] ?? 'None' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Last Weight Record</span>
                        <span class="text-gray-900">{{ $stats['last_weight_date'] ?? 'None' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Last Feed Record</span>
                        <span class="text-gray-900">{{ $stats['last_feed_date'] ?? 'None' }}</span>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection