@extends('layouts.app')

@section('title', 'Staff Dashboard - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Staff Dashboard</h1>
        <p class="text-sm text-gray-600">Welcome back, {{ auth()->user()->name }}. Here are your daily tasks.</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <a href="{{ route('poultry.forms.hub') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <i class="fas fa-edit mr-2"></i> Go to Form Hub
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Your Records</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ ($stats['flock_records'] ?? 0) + ($stats['weight_records'] ?? 0) + ($stats['feed_records'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-500 flex items-center justify-center">
                    <i class="fas fa-database text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-2 text-sm">
                <div class="text-center"><p class="font-bold text-gray-900">{{ $stats['flock_records'] ?? 0 }}</p><p class="text-xs text-gray-500">Flock</p></div>
                <div class="text-center"><p class="font-bold text-gray-900">{{ $stats['weight_records'] ?? 0 }}</p><p class="text-xs text-gray-500">Weight</p></div>
                <div class="text-center"><p class="font-bold text-gray-900">{{ $stats['feed_records'] ?? 0 }}</p><p class="text-xs text-gray-500">Feed</p></div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Your Batches</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['active_batches'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-500 flex items-center justify-center">
                    <i class="fas fa-layer-group text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-500">
                Total created: {{ $stats['batches_created'] ?? 0 }}
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Today's Tasks</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $todayTasksCount ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-yellow-500 flex items-center justify-center">
                    <i class="fas fa-clipboard-check text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-500">
                {{ $todayTasksCount == 0 ? 'All done!' : $todayTasksCount.' tasks pending' }}
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Last Entry</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">
                        {{ $stats['last_flock_date'] ?? ($stats['last_weight_date'] ?? ($stats['last_feed_date'] ?? 'N/A')) }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-purple-500 flex items-center justify-center">
                    <i class="fas fa-clock text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-500">
                {{ isset($stats['last_flock_date']) ? 'Last flock record' : (isset($stats['last_weight_date']) ? 'Last weight record' : 'No entries yet') }}
            </div>
        </div>
    </div>

    <!-- Today's Tasks -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-primary-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-tasks text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Today's Tasks</h3>
                        <p class="text-sm text-gray-600">Complete your daily responsibilities</p>
                    </div>
                </div>
                @if($todayTasksCount ?? 0 > 0)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ $todayTasksCount }} pending
                </span>
                @endif
            </div>
        </div>
        <div class="p-6">
            @if($todaySchedules ?? false && count($todaySchedules) > 0)
            <div class="space-y-4">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Weighing Tasks for Today</h4>
                @foreach($todaySchedules as $schedule)
                <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-white border border-blue-200 flex items-center justify-center">
                            <i class="fas fa-weight text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">{{ $schedule->batch->batch_id }}</p>
                            <p class="text-xs text-gray-500">{{ $schedule->batch->name }} ({{ $schedule->batch->current_age_days }} days old)</p>
                        </div>
                    </div>
                    <a href="{{ route('poultry.forms.weight-record.create', ['batch' => $schedule->batch_id]) }}" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-primary-600 text-white hover:bg-primary-700">
                        <i class="fas fa-plus mr-1"></i> Record Weight
                    </a>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-8">
                <div class="w-16 h-16 mx-auto rounded-full bg-green-100 flex items-center justify-center mb-4">
                    <i class="fas fa-check text-green-600 text-xl"></i>
                </div>
                <h4 class="text-lg font-medium text-gray-900 mb-2">All caught up!</h4>
                <p class="text-gray-600 mb-4">No weighing tasks scheduled for today.</p>
                <a href="{{ route('poultry.forms.hub') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">
                    <i class="fas fa-edit mr-2"></i> Record Daily Data
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- Quick Forms -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Quick Data Entry</h3>
            <a href="{{ route('poultry.forms.hub') }}" class="text-sm text-primary-600 hover:text-primary-700">View all forms</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <a href="{{ route('poultry.forms.flock-record.create') }}" class="group p-4 bg-red-50 hover:bg-red-100 rounded-xl border border-red-100 transition-all">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center"><span class="text-xl">🐔</span></div>
                    <div class="ml-3"><h4 class="text-sm font-medium text-gray-900">Flock Record</h4><p class="text-xs text-gray-500">Mortality, culls, slaughter</p></div>
                </div>
                <p class="text-xs text-gray-600 mb-3">Record daily flock changes</p>
                <div class="flex items-center text-sm text-red-600"><span>Start recording</span><i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i></div>
            </a>

            <a href="{{ route('poultry.forms.weight-record.create') }}" class="group p-4 bg-blue-50 hover:bg-blue-100 rounded-xl border border-blue-100 transition-all">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center"><span class="text-xl">⚖️</span></div>
                    <div class="ml-3"><h4 class="text-sm font-medium text-gray-900">Weight Record</h4><p class="text-xs text-gray-500">Bird weights with CV</p></div>
                </div>
                <p class="text-xs text-gray-600 mb-3">Record individual bird weights</p>
                <div class="flex items-center text-sm text-blue-600"><span>Start recording</span><i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i></div>
            </a>

            <a href="{{ route('poultry.forms.feed-record.create') }}" class="group p-4 bg-green-50 hover:bg-green-100 rounded-xl border border-green-100 transition-all">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center"><span class="text-xl">🍽️</span></div>
                    <div class="ml-3"><h4 class="text-sm font-medium text-gray-900">Feed Record</h4><p class="text-xs text-gray-500">Feed consumption</p></div>
                </div>
                <p class="text-xs text-gray-600 mb-3">Record daily feed usage</p>
                <div class="flex items-center text-sm text-green-600"><span>Start recording</span><i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i></div>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Recent Flock Records</h3>
            <div class="space-y-3">
                @forelse($recentFlock ?? [] as $record)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $record->batch->batch_id ?? 'Unknown' }}</p>
                        <p class="text-xs text-gray-500">{{ $record->date->format('M d, Y') }}</p>
                    </div>
                    <div class="flex items-center space-x-3 text-sm">
                        @if($record->mortality > 0)<span class="text-red-600">M: {{ $record->mortality }}</span>@endif
                        @if($record->culls > 0)<span class="text-yellow-600">C: {{ $record->culls }}</span>@endif
                        @if($record->slaughter > 0)<span class="text-green-600">S: {{ $record->slaughter }}</span>@endif
                        @if($record->mortality == 0 && $record->culls == 0 && $record->slaughter == 0)
                        <span class="text-gray-400">No changes</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-kiwi-bird text-2xl mb-2"></i>
                    <p>No recent flock records</p>
                </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Recent Weight Records</h3>
            <div class="space-y-3">
                @forelse($recentWeight ?? [] as $record)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $record->batch->batch_id ?? 'Unknown' }}</p>
                        <p class="text-xs text-gray-500">{{ $record->date->format('M d, Y') }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-medium">{{ format_weight($record->average_weight) }}</div>
                        <div class="mt-1">{!! cv_status_badge($record->cv_status) !!}</div>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-weight text-2xl mb-2"></i>
                    <p>No recent weight records</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Staff Tips -->
    <div class="bg-yellow-50 rounded-xl border border-yellow-200 p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0"><i class="fas fa-lightbulb text-yellow-500 text-xl"></i></div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-yellow-800">Quick Tips</h3>
                <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside space-y-1">
                    <li>Always wear protective gear when handling birds</li>
                    <li>Weigh birds at the same time each day for consistency</li>
                    <li>Ensure proper sample size (10% of flock, min 5, max 10 birds)</li>
                    <li>Report any unusual mortality immediately to your manager</li>
                    <li>Keep weighing equipment clean and calibrated</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection