@auth
<header class="border-b border-gray-200 bg-white">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Left section -->
            <div class="flex items-center">
                <button @click="sidebarOpen = true" class="lg:hidden -ml-2 mr-2 p-2 text-gray-400 hover:text-gray-500 focus:outline-none">
                    <i class="fas fa-bars h-6 w-6"></i>
                </button>

                @yield('breadcrumb')
            </div>

            <!-- Right section -->
            <div class="flex items-center space-x-4">
                <!-- Notifications -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="p-2 text-gray-400 hover:text-gray-500 relative">
                        <i class="fas fa-bell h-6 w-6"></i>
                        @if($unreadNotificationsCount ?? 0 > 0)
                            <span class="absolute top-1 right-1 block h-2 w-2 rounded-full bg-red-400"></span>
                        @endif
                    </button>

                    <div x-show="open"
                         @click.away="open = false"
                         class="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         style="display: none;">
                        <div class="py-2">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                                @if(($unreadNotificationsCount ?? 0) > 0)
                                    <span class="text-xs text-gray-500">{{ $unreadNotificationsCount }} unread</span>
                                @endif
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                @forelse($recentNotifications ?? [] as $readStatus)
                                    <a href="{{ route('notifications.index') }}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0">
                                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <i class="fas fa-{{ $readStatus->notification->notification_type === 'weighing_day' ? 'weight' : ($readStatus->notification->notification_type === 'missed_weighing' ? 'exclamation-triangle' : 'info') }} text-blue-600"></i>
                                                </div>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm font-medium text-gray-900">{{ $readStatus->notification->title }}</p>
                                                <p class="text-xs text-gray-500 mt-1">{{ Str::limit($readStatus->notification->message, 50) }}</p>
                                                <p class="text-xs text-gray-400 mt-1">{{ $readStatus->notification->created_at->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <div class="px-4 py-8 text-center">
                                        <i class="fas fa-bell-slash text-gray-300 text-2xl mb-2"></i>
                                        <p class="text-sm text-gray-500">No notifications</p>
                                    </div>
                                @endforelse
                            </div>
                            <div class="border-t border-gray-100">
                                <a href="{{ route('notifications.index') }}" class="block px-4 py-2 text-sm text-center text-primary-600 hover:text-primary-700">
                                    View all notifications
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Badges -->
                <div class="hidden md:flex items-center space-x-2">
                    @if(($todayTasksCount ?? 0) > 0)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-tasks mr-1"></i> {{ $todayTasksCount }} tasks
                        </span>
                    @endif

                    @if(($lowStockCount ?? 0) > 0)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-exclamation-triangle mr-1"></i> {{ $lowStockCount }} low stock
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
@endauth