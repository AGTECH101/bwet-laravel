@extends('layouts.app')

@section('title', 'Admin Dashboard - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
        <p class="text-sm text-gray-600">Welcome back, {{ auth()->user()->name }}. Last refreshed: {{ now()->format('H:i:s') }}</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
        <form method="POST" action="{{ route('quick-refresh') }}" class="inline">
            @csrf
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <i class="fas fa-sync-alt mr-2"></i> Refresh
            </button>
        </form>
        <a href="{{ route('poultry.batches.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <i class="fas fa-plus mr-2"></i> New Batch
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
                    <p class="text-sm font-medium text-gray-600">Total Batches</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $overview['total_batches'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-500 flex items-center justify-center">
                    <i class="fas fa-layer-group text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-center text-sm">
                    <span class="text-green-600 font-medium">
                        <i class="fas fa-arrow-up mr-1"></i> {{ $overview['active_batches'] ?? 0 }} active
                    </span>
                    <span class="ml-4 text-gray-500">{{ $overview['completed_batches'] ?? 0 }} completed</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Investment</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ format_currency($overview['total_active_investment'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-500 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-sm text-gray-500">
                    Expenses: {{ format_currency($financial['total_expenses'] ?? 0) }}
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Inventory Value</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ format_currency($financial['inventory_value'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-yellow-500 flex items-center justify-center">
                    <i class="fas fa-boxes text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                @if(($alerts['low_stock_items'] ?? 0) > 0)
                    <div class="text-sm text-red-600 font-medium">
                        <i class="fas fa-exclamation-triangle mr-1"></i> {{ $alerts['low_stock_items'] }} items low
                    </div>
                @else
                    <div class="text-sm text-green-600 font-medium">
                        <i class="fas fa-check-circle mr-1"></i> Stock levels good
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending Approvals</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $overview['pending_approvals'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-purple-500 flex items-center justify-center">
                    <i class="fas fa-user-clock text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-sm text-gray-500">
                    Total Users: {{ $overview['total_users'] ?? 0 }}
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Batches & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Recent Batches</h3>
                <a href="{{ route('poultry.batches.index') }}" class="text-sm text-primary-600 hover:text-primary-700">
                    View all <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Flock</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">FCR</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($recentBatches ?? [] as $batch)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('poultry.batches.show', $batch) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                                    {{ $batch->batch_id }}
                                </a>
                                <p class="text-xs text-gray-500">{{ Str::limit($batch->name, 20) }}</p>
                            </td>
                            <td class="px-4 py-3">{!! batch_status_badge($batch->status) !!}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $batch->current_age_days }} days</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($batch->remaining_flock / max($batch->starting_flock, 1)) * 100 }}%"></div>
                                    </div>
                                    <span class="text-sm text-gray-900">{{ $batch->remaining_flock }}/{{ $batch->starting_flock }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium {{ $batch->current_cfcr < 1.8 ? 'text-green-600' : ($batch->current_cfcr < 2.0 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ format_fcr($batch->current_cfcr) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-2xl mb-2"></i>
                                <p>No batches found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Quick Actions</h3>
            <div class="space-y-3">
                <a href="{{ route('poultry.batches.create') }}" class="flex items-center justify-between p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-all">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-plus text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">New Batch</p>
                            <p class="text-xs text-gray-500">Start poultry production</p>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>

                <a href="{{ route('poultry.inventory.create') }}" class="flex items-center justify-between p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-all">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="fas fa-box text-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">Add Inventory</p>
                            <p class="text-xs text-gray-500">Register new stock items</p>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>

                <a href="{{ route('admin.users.index') }}" class="flex items-center justify-between p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition-all">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                            <i class="fas fa-users text-purple-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">Manage Users</p>
                            <p class="text-xs text-gray-500">Approve or manage accounts</p>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Today's Tasks & System Alerts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Today's Tasks</h3>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ $todayTasksCount ?? 0 }} tasks
                </span>
            </div>
            <div class="space-y-4">
                @forelse($todayTasks ?? [] as $task)
                <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-{{ $task['icon'] ?? 'tasks' }} text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">{{ $task['message'] }}</p>
                            <p class="text-xs text-gray-500">{{ $task['batch'] ?? 'System' }}</p>
                        </div>
                    </div>
                    <a href="{{ $task['action_url'] ?? '#' }}" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                @empty
                <div class="text-center py-8">
                    <i class="fas fa-check-circle text-green-300 text-3xl mb-2"></i>
                    <p class="text-gray-500">All tasks completed for today</p>
                </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">System Overview</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Average FCR</span>
                    <span class="text-sm font-bold text-gray-900">{{ $avg_cfcr ?? '0.000' }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Average Mortality</span>
                    <span class="text-sm font-bold text-gray-900">{{ $avg_mortality ?? '0.0' }}%</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Active Users</span>
                    <span class="text-sm font-bold text-gray-900">{{ $overview['total_users'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Pending Approvals</span>
                    <span class="text-sm font-bold text-yellow-600">{{ $overview['pending_approvals'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Observation Stats -->
    @if(isset($observations) && !empty($observations))
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Observation Overview</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-2xl font-bold text-gray-900">{{ $observations['total'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Total Reports</p>
            </div>
            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                <p class="text-2xl font-bold text-yellow-600">{{ $observations['pending'] ?? 0 }}</p>
                <p class="text-sm text-yellow-600">Pending Review</p>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <p class="text-2xl font-bold text-green-600">{{ $observations['resolved'] ?? 0 }}</p>
                <p class="text-sm text-green-600">Resolved</p>
            </div>
            <div class="text-center p-4 bg-red-50 rounded-lg">
                <p class="text-2xl font-bold text-red-600">{{ collect($observations['by_priority'] ?? [])->firstWhere('priority', 'critical')['count'] ?? 0 }}</p>
                <p class="text-sm text-red-600">Critical</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Performance Chart -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Batch Performance Overview</h3>
            <a href="{{ route('poultry.analytics.global') }}" class="text-sm text-primary-600 hover:text-primary-700">
                View full analytics <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div style="height: 300px;">
            <canvas id="adminDashboardChart"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('adminDashboardChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6', 'Week 7', 'Week 8'],
                datasets: [{
                    label: 'Average Weight (kg)',
                    data: [0.18, 0.45, 0.85, 1.30, 1.80, 2.20, 2.50, 2.70],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'FCR (cumulative)',
                    data: [0.0, 1.2, 1.4, 1.6, 1.7, 1.8, 1.85, 1.90],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Value'
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush