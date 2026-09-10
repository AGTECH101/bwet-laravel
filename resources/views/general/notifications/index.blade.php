@extends('layouts.app')

@section('title', 'Notifications - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
        <p class="text-sm text-gray-600">Stay updated with system alerts and messages</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <form method="POST" action="{{ route('notifications.clear-all') }}" class="inline">
            @csrf
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <i class="fas fa-check-double mr-2"></i> Mark All Read
            </button>
        </form>
    </div>
</div>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">All Notifications</h3>
                <p class="text-sm text-gray-600 mt-1">
                    Total: {{ count($notifications) }} notifications
                </p>
            </div>
        </div>
    </div>

    <div class="divide-y divide-gray-200">
        @forelse($notifications as $notification)
        @php
            $type = $notification['type'] ?? 'general';
            
            // Map notification type to icon and colors
            $iconMap = [
                'manual_mode' => ['icon' => 'user-cog', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-600'],
                'system' => ['icon' => 'cog', 'bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
                'batch_transfer' => ['icon' => 'exchange-alt', 'bg' => 'bg-green-100', 'text' => 'text-green-600'],
                'weighing_day' => ['icon' => 'weight', 'bg' => 'bg-indigo-100', 'text' => 'text-indigo-600'],
                'slaughter_trigger' => ['icon' => 'exclamation-triangle', 'bg' => 'bg-red-100', 'text' => 'text-red-600'],
                'missed_weighing' => ['icon' => 'clock', 'bg' => 'bg-orange-100', 'text' => 'text-orange-600'],
                'low_stock' => ['icon' => 'box', 'bg' => 'bg-amber-100', 'text' => 'text-amber-600'],
                'default' => ['icon' => 'bell', 'bg' => 'bg-gray-100', 'text' => 'text-gray-600'],
            ];
            
            $iconData = $iconMap[$type] ?? $iconMap['default'];
        @endphp
        <div class="p-6 hover:bg-gray-50 transition-colors {{ !$notification['is_read'] ? 'bg-blue-50' : '' }}">
            <div class="flex items-start">
                <!-- Icon -->
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full {{ $iconData['bg'] }} flex items-center justify-center">
                        <i class="fas fa-{{ $iconData['icon'] }} {{ $iconData['text'] }}"></i>
                    </div>
                </div>

                <!-- Content -->
                <div class="ml-4 flex-1 min-w-0">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-medium text-gray-900">{{ $notification['title'] ?? 'Notification' }}</h4>
                            <p class="text-sm text-gray-600 mt-1">{{ $notification['message'] ?? '' }}</p>
                            @if($notification['batch_id'] ?? false)
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-layer-group mr-1"></i> 
                                    <a href="{{ route('poultry.batches.show', $notification['batch_id']) }}" class="hover:underline">
                                        Batch {{ $notification['batch_id'] }}
                                    </a>
                                </span>
                            </div>
                            @endif
                        </div>
                        <div class="flex flex-col items-end flex-shrink-0 ml-2">
                            <span class="text-xs text-gray-500 whitespace-nowrap">{{ $notification['created_at'] ? $notification['created_at']->diffForHumans() : 'N/A' }}</span>
                            @if(!($notification['is_read'] ?? true))
                            <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">New</span>
                            @endif
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center space-x-4">
                            @if($notification['batch_id'] ?? false)
                            <a href="{{ route('poultry.batches.show', $notification['batch_id']) }}" class="text-sm text-primary-600 hover:text-primary-700">
                                View Batch <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                            @endif
                            @if($notification['observation_report_id'] ?? false)
                            <a href="{{ route('observations.show', $notification['observation_report_id']) }}" class="text-sm text-primary-600 hover:text-primary-700">
                                View Observation <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                            @endif
                        </div>
                        @if(!($notification['is_read'] ?? true))
                        <form method="POST" action="{{ route('notifications.read', $notification['id']) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">
                                <i class="fas fa-check mr-1"></i> Mark read
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="p-12 text-center">
            <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-4">
                <i class="fas fa-bell-slash text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications</h3>
            <p class="text-gray-500">You're all caught up! No notifications at the moment.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection