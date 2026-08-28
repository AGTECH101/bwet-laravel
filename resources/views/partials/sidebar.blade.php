@php
    $selectedSector = \App\Models\Sector::find(session('selected_sector_id'));
    $sectorSlug = $selectedSector ? $selectedSector->slug : null;
    $userRole = auth()->user()->role ?? null;
@endphp

<div class="flex flex-col h-full md:h-[100vh] md:fixed">
    <!-- Sidebar header -->
    <div class="flex items-center h-16 flex-shrink-0 px-4 border-b border-gray-200">
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
            <div class="w-12 h-12 rounded-2xl overflow-hidden bg-white border border-gray-200 shadow-sm">
                <img src="{{ asset('favicon.ico') }}" alt="BWET Farms logo" class="w-full h-full object-cover rounded-2xl">
            </div>
            <span class="text-xl font-bold text-gray-900">BWET Farms</span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}"
           class="{{ request()->routeIs('dashboard') ? 'bg-primary-50 text-primary-700 border-primary-500' : 'text-gray-700 hover:bg-gray-100' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg border-l-4 border-transparent transition-colors">
            <i class="fas fa-tachometer-alt mr-3 text-gray-400 group-hover:text-gray-500 {{ request()->routeIs('dashboard') ? 'text-primary-500' : '' }}"></i>
            Dashboard
        </a>

        @if(in_array($userRole, ['admin', 'manager', 'staff'], true))
            <a href="{{ route('poultry.batches.index') }}"
               class="{{ request()->routeIs('poultry.batches.*') ? 'bg-primary-50 text-primary-700 border-primary-500' : 'text-gray-700 hover:bg-gray-100' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg border-l-4 border-transparent">
                <i class="fas fa-layer-group mr-3 text-gray-400 group-hover:text-gray-500 {{ request()->routeIs('poultry.batches.*') ? 'text-primary-500' : '' }}"></i>
                Batches
            </a>
        @endif

        @if(in_array($userRole, ['admin', 'manager', 'staff'], true))
            <a href="{{ route('poultry.forms.hub') }}"
               class="{{ request()->routeIs('poultry.forms.*') ? 'bg-primary-50 text-primary-700 border-primary-500' : 'text-gray-700 hover:bg-gray-100' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg border-l-4 border-transparent">
                <i class="fas fa-edit mr-3 text-gray-400 group-hover:text-gray-500 {{ request()->routeIs('poultry.forms.*') ? 'text-primary-500' : '' }}"></i>
                Form Hub
            </a>
        @endif

        @if(in_array($userRole, ['admin', 'manager', 'staff'], true))
            <a href="{{ route('poultry.inventory.index') }}"
               class="{{ request()->routeIs('poultry.inventory.*') ? 'bg-primary-50 text-primary-700 border-primary-500' : 'text-gray-700 hover:bg-gray-100' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg border-l-4 border-transparent">
                <i class="fas fa-boxes mr-3 text-gray-400 group-hover:text-gray-500 {{ request()->routeIs('poultry.inventory.*') ? 'text-primary-500' : '' }}"></i>
                Inventory
            </a>
        @endif

        {{-- @if(in_array($userRole, ['admin', 'investor'], true))
            <a href="{{ route('poultry.analytics.global') }}"
               class="{{ request()->routeIs('poultry.analytics.*') ? 'bg-primary-50 text-primary-700 border-primary-500' : 'text-gray-700 hover:bg-gray-100' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg border-l-4 border-transparent">
                <i class="fas fa-chart-line mr-3 text-gray-400 group-hover:text-gray-500 {{ request()->routeIs('poultry.analytics.*') ? 'text-primary-500' : '' }}"></i>
                Analytics
            </a>
        @endif --}}

        @if(in_array($userRole, ['admin', 'manager'], true))
            <a href="{{ route('observations.index') }}"
               class="{{ request()->routeIs('observations.*') ? 'bg-primary-50 text-primary-700 border-primary-500' : 'text-gray-700 hover:bg-gray-100' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg border-l-4 border-transparent">
                <i class="fas fa-clipboard-list mr-3 text-gray-400 group-hover:text-gray-500 {{ request()->routeIs('observations.*') ? 'text-primary-500' : '' }}"></i>
                Observations
            </a>
        @endif

        @if(!in_array($userRole, ['staff'], true))
            <a href="{{ route('history.index') }}"
               class="{{ request()->routeIs('history.*') ? 'bg-primary-50 text-primary-700 border-primary-500' : 'text-gray-700 hover:bg-gray-100' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg border-l-4 border-transparent">
                <i class="fas fa-history mr-3 text-gray-400 group-hover:text-gray-500 {{ request()->routeIs('history.*') ? 'text-primary-500' : '' }}"></i>
                History
            </a>
        @endif

        {{-- @if(in_array($userRole, ['admin', 'manager'], true))
            <a href="{{ route('export.index') }}"
               class="{{ request()->routeIs('export.*') ? 'bg-primary-50 text-primary-700 border-primary-500' : 'text-gray-700 hover:bg-gray-100' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg border-l-4 border-transparent">
                <i class="fas fa-file-export mr-3 text-gray-400 group-hover:text-gray-500 {{ request()->routeIs('export.*') ? 'text-primary-500' : '' }}"></i>
                Export Data
            </a>
        @endif --}}

        @if(in_array($userRole, ['admin', 'manager'], true))
            <a href="{{ route('admin.users.create') }}"
               class="{{ request()->routeIs('admin.users.create') ? 'bg-primary-50 text-primary-700 border-primary-500' : 'text-gray-700 hover:bg-gray-100' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg border-l-4 border-transparent">
                <i class="fas fa-user-plus mr-3 text-gray-400 group-hover:text-gray-500 {{ request()->routeIs('admin.users.create') ? 'text-primary-500' : '' }}"></i>
                Create User
            </a>
        @endif

        @if(in_array($userRole, ['admin', 'manager'], true))
        <a href="{{ route('admin.users.index') }}"
           class="{{ request()->routeIs('admin.users.*') ? 'bg-primary-50 text-primary-700 border-primary-500' : 'text-gray-700 hover:bg-gray-100' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg border-l-4 border-transparent">
            <i class="fas fa-users mr-3 text-gray-400 group-hover:text-gray-500 {{ request()->routeIs('admin.users.*') ? 'text-primary-500' : '' }}"></i>
            User Management
        </a>
        @endif

        @if($userRole === 'admin')
        <a href="{{ route('system.variables.index') }}"
           class="{{ request()->routeIs('system.variables.*') ? 'bg-primary-50 text-primary-700 border-primary-500' : 'text-gray-700 hover:bg-gray-100' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg border-l-4 border-transparent">
            <i class="fas fa-cog mr-3 text-gray-400 group-hover:text-gray-500 {{ request()->routeIs('system.variables.*') ? 'text-primary-500' : '' }}"></i>
            System Settings
        </a>
        @endif

        @if(in_array($userRole, ['admin', 'manager'], true))
        <a href="{{ route('system.market-prices.index') }}"
           class="{{ request()->routeIs('system.market-prices.*') ? 'bg-primary-50 text-primary-700 border-primary-500' : 'text-gray-700 hover:bg-gray-100' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg border-l-4 border-transparent">
            <i class="fas fa-tag mr-3 text-gray-400 group-hover:text-gray-500 {{ request()->routeIs('system.market-prices.*') ? 'text-primary-500' : '' }}"></i>
            Market Prices
        </a>
        @endif
    </nav>

    <!-- Sector switcher -->
    <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
        <div class="flex items-center w-full">
            <div class="flex-shrink-0">
                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                    <i class="fas fa-{{ $selectedSector?->icon ?? 'building' }} text-gray-600"></i>
                </div>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-gray-900">{{ $selectedSector?->name ?? 'Select Sector' }}</p>
                <a href="{{ route('sectors.index') }}" class="text-xs text-primary-600 hover:text-primary-700">Change Sector</a>
            </div>
            <div class="flex-shrink-0 ml-3">
                <!-- User dropdown (same as before) -->
            </div>
        </div>
    </div>

    <!-- User profile section (existing) -->
    <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
        <div class="flex items-center w-full">
            <div class="flex-shrink-0">
                <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                    <i class="fas fa-user text-primary-600"></i>
                </div>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                <div class="flex items-center">
                    <span class="text-xs text-gray-500">{{ ucfirst(auth()->user()->role) }}</span>
                    @if(!auth()->user()->is_approved)
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                    @endif
                </div>
            </div>
            <div class="relative flex-shrink-0 ml-3" x-data="{ open: false }">
                <button @click="open = !open" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div x-show="open"
                     @click.away="open = false"
                     class="origin-top-right absolute right-0 bottom-full mb-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10"
                     style="display: none;">
                    <div class="py-1">
                        <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user-circle mr-2"></i> Profile
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>