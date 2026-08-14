@extends('layouts.app')

@section('title', 'User Management - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
        <p class="text-sm text-gray-600">Manage user accounts, roles, and permissions</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <i class="fas fa-filter mr-2"></i> Filter <i class="fas fa-chevron-down ml-2"></i>
            </button>
            <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10" style="display: none;">
                <div class="py-1">
                    <a href="{{ route('admin.users.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">All Users</a>
                    <a href="{{ route('admin.users.index', ['role' => 'admin']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Admins</a>
                    <a href="{{ route('admin.users.index', ['role' => 'manager']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Managers</a>
                    <a href="{{ route('admin.users.index', ['role' => 'staff']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Staff</a>
                    <a href="{{ route('admin.users.index', ['role' => 'investor']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Investors</a>
                    <div class="border-t border-gray-100"></div>
                    <a href="{{ route('admin.users.index', ['approved' => 'pending']) }}" class="block px-4 py-2 text-sm text-yellow-700 hover:bg-gray-100">Pending Approval</a>
                    <a href="{{ route('admin.users.index', ['approved' => 'approved']) }}" class="block px-4 py-2 text-sm text-green-700 hover:bg-gray-100">Approved</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Users</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $users->total() }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-primary-500 flex items-center justify-center">
                    <i class="fas fa-users text-white text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Approved</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ $users->filter(fn($u) => $u->is_approved)->count() }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-500 flex items-center justify-center">
                    <i class="fas fa-user-check text-white text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending Approval</p>
                    <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $pendingCount }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-yellow-500 flex items-center justify-center">
                    <i class="fas fa-user-clock text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                    <i class="fas fa-user text-primary-600"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                {{ $user->role == 'admin' ? 'bg-red-100 text-red-800' :
                                   ($user->role == 'manager' ? 'bg-blue-100 text-blue-800' :
                                   ($user->role == 'staff' ? 'bg-green-100 text-green-800' :
                                   'bg-purple-100 text-purple-800')) }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div>{{ $user->phone ?? 'No phone' }}</div>
                            <div class="text-xs">{{ $user->farm_location ?? 'No location' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($user->is_approved)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i> Approved
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-clock mr-1"></i> Pending
                                </span>
                            @endif
                            @if(!$user->is_active)
                                <span class="ml-2 inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-ban mr-1"></i> Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $user->created_at->format('M d, Y') }}
                            <div class="text-xs text-gray-400">{{ $user->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                @if(!$user->is_approved && $user->role != 'admin')
                                <form method="POST" action="{{ route('admin.users.approve', $user) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-900" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                @endif

                                @if($user->is_active && $user->id != auth()->id())
                                <form method="POST" action="{{ route('admin.users.deactivate', $user) }}" class="inline" onsubmit="return confirm('Deactivate {{ $user->name }}?')">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Deactivate">
                                        <i class="fas fa-user-slash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-users text-2xl mb-2"></i>
                            <p>No users found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $users->links() }}
        </div>
        @endif
    </div>

    <!-- Pending Approvals Section -->
    @if($pendingCount > 0)
    <div class="bg-yellow-50 rounded-xl border border-yellow-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-yellow-800">Pending Approvals ({{ $pendingCount }})</h3>
            <a href="{{ route('admin.users.index', ['approved' => 'pending']) }}" class="text-sm text-yellow-700 hover:text-yellow-800">
                View all <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="space-y-3">
            @foreach($users as $user)
            @if(!$user->is_approved && $user->role != 'admin')
            <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-yellow-100">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                        <i class="fas fa-user-clock text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                        <p class="text-xs text-gray-500">{{ ucfirst($user->role) }} • {{ $user->email }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-xs text-gray-500">{{ $user->created_at->format('M d') }}</span>
                    <form method="POST" action="{{ route('admin.users.approve', $user) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200">
                            <i class="fas fa-check mr-1"></i> Approve
                        </button>
                    </form>
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection