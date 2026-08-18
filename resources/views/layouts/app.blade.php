<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <title>@yield('title', config('app.name', 'BWET Farms'))</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50:  '#f1f8f4',
                            100: '#dcefe3',
                            500: '#2f855a',
                            600: '#276749',
                            700: '#1f4d36',
                        },
                        secondary: {
                            50:  '#f8f5f2',
                            100: '#ede6df',
                            500: '#8b5e3c',
                            600: '#6f4a2e',
                            700: '#55351f',
                        },
                        success: '#10b981',
                        warning: '#f59e0b',
                        danger: '#ef4444',
                        info: '#3b82f6',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@1.9.2"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50" x-data="{ sidebarOpen: false, notificationsOpen: false }">

    <!-- Notification Toasts -->
    @include('partials.alerts')

    @auth
        <!-- Mobile sidebar backdrop -->
        <div class="lg:hidden fixed inset-0 bg-gray-600 bg-opacity-75 z-30 transition-opacity"
             x-show="sidebarOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             style="display: none;"></div>

        <!-- Mobile sidebar -->
        <div class="lg:hidden fixed inset-y-0 left-0 z-40 w-64 bg-white shadow-lg transform transition-transform duration-300"
             x-show="sidebarOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             style="display: none;">
            @include('partials.sidebar')
        </div>

        <div class="flex min-h-screen">
            <!-- Desktop sidebar -->
            <div class="hidden lg:flex lg:flex-shrink-0">
                <div class="flex flex-col w-64 border-r border-gray-200 bg-white">
                    @include('partials.sidebar')
                </div>
            </div>

            <!-- Main content -->
            <div class="flex-1 flex flex-col min-h-screen">
                @include('partials.topbar')
                <main class="flex-1 overflow-y-auto bg-gray-50 focus:outline-none">
                    <div class="py-6">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                            @yield('page_header')
                            @yield('content')
                        </div>
                    </div>
                </main>
            </div>
        </div>
    @else
        <!-- Public layout (no sidebar) -->
        <main class="min-h-screen bg-gray-50">
            @yield('content')
        </main>
    @endauth

    <!-- Chart.js (only when needed) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom JS -->
    <script src="{{ asset('js/app.js') }}"></script>

    @stack('scripts')
</body>
</html>