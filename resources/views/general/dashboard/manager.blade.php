@extends('layouts.app')

@section('title', 'Manager Dashboard - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Manager Dashboard</h1>
        <p class="text-sm text-gray-600">Welcome back, {{ auth()->user()->name }}</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
        <a href="{{ route('poultry.batches.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <i class="fas fa-plus mr-2"></i> New Batch
        </a>
        <a href="{{ route('observations.create') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <i class="fas fa-clipboard mr-2"></i> Report Observation
        </a>
        <a href="{{ route('poultry.inventory.waste') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <i class="fas fa-trash-alt mr-2"></i> Waste Log
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Batches</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ count($batches ?? []) }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-500 flex items-center justify-center">
                    <i class="fas fa-layer-group text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Avg. Mortality</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($overall_metrics['avg_mortality'] ?? 0, 1) }}%</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-500 flex items-center justify-center">
                    <i class="fas fa-heartbeat text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Avg. cFCR</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($overall_metrics['avg_cfcr'] ?? 0, 3) }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-yellow-500 flex items-center justify-center">
                    <i class="fas fa-chart-line text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Feed Used</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($overall_metrics['total_feed_used'] ?? 0, 0) }} kg</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-purple-500 flex items-center justify-center">
                    <i class="fas fa-utensils text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Batches Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">Active Batches</h3>
            <a href="{{ route('poultry.batches.index') }}" class="text-sm text-primary-600 hover:text-primary-700">
                View all <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Weight</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">FCR</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profit %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($batches ?? [] as $batch)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('poultry.batches.show', $batch) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                                {{ $batch->batch_id }}
                            </a>
                            <div class="text-xs text-gray-500">{{ Str::limit($batch->name, 25) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $batch->age_days }} days</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ ($batch->remaining_flock / max($batch->starting_flock, 1)) * 100 }}%"></div>
                                </div>
                                <span class="text-sm text-gray-900">{{ $batch->remaining_flock }}/{{ $batch->starting_flock }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ format_weight($batch->getCurrentAverageWeight()) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $batch->current_cfcr < 1.8 ? 'text-green-600' : ($batch->current_cfcr < 2.0 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ format_fcr($batch->current_cfcr) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $batch->current_marginal_profit_percent > 20 ? 'text-green-600' : 'text-yellow-600' }}">
                            {{ format_percentage($batch->current_marginal_profit_percent) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('poultry.batches.show', $batch) }}" class="text-primary-600 hover:text-primary-900 mr-2" title="View"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('poultry.forms.hub', ['batch' => $batch->batch_id]) }}" class="text-green-600 hover:text-green-900 mr-2" title="Add Record"><i class="fas fa-plus"></i></a>
                            <a href="{{ route('poultry.batches.export', $batch) }}" class="text-purple-600 hover:text-purple-900" title="Export"><i class="fas fa-file-export"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-2xl mb-2"></i>
                            <p>No active batches found</p>
                            <a href="{{ route('poultry.batches.create') }}" class="mt-2 inline-block text-sm text-primary-600 hover:text-primary-700">
                                Create your first batch
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Alerts & Observations -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Stock Alerts -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Stock Alerts</h3>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ (count($low_stock_items ?? []) + count($out_of_stock_items ?? [])) > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                    {{ count($low_stock_items ?? []) + count($out_of_stock_items ?? []) }} alerts
                </span>
            </div>
            <div class="space-y-3">
                @forelse($low_stock_items ?? [] as $item)
                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg border border-yellow-100">
                    <div>
                        <p class="text-sm font-medium text-yellow-800">{{ $item->name }}</p>
                        <p class="text-xs text-yellow-600">Low: {{ $item->quantity_in_stock }} {{ $item->unit }} (min: {{ $item->minimum_quantity }})</p>
                    </div>
                    <a href="{{ route('poultry.inventory.show', $item) }}" class="text-yellow-600 hover:text-yellow-700">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                @empty
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-check-circle text-green-300 text-2xl mb-2"></i>
                    <p class="text-sm">All stock levels are healthy</p>
                </div>
                @endforelse

                @forelse($out_of_stock_items ?? [] as $item)
                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg border border-red-100">
                    <div>
                        <p class="text-sm font-medium text-red-800">{{ $item->name }}</p>
                        <p class="text-xs text-red-600">Out of stock!</p>
                    </div>
                    <a href="{{ route('poultry.inventory.show', $item) }}" class="text-red-600 hover:text-red-700">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                @empty
                @endforelse
            </div>
        </div>

        <!-- Recent Observations -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Recent Observations</h3>
                <a href="{{ route('observations.index') }}" class="text-sm text-primary-600 hover:text-primary-700">
                    View all <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="space-y-3">
                @forelse($observations['my_recent'] ?? [] as $obs)
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ Str::limit($obs->title, 40) }}</p>
                            <div class="flex items-center mt-1 space-x-2">
                                <span class="text-xs text-gray-500">{{ $obs->created_at->diffForHumans() }}</span>
                                {!! observation_status_badge($obs->status) !!}
                                {!! priority_badge($obs->priority) !!}
                            </div>
                        </div>
                        <a href="{{ route('observations.show', $obs) }}" class="text-primary-600 hover:text-primary-700">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-clipboard text-2xl mb-2"></i>
                    <p class="text-sm">No recent observations</p>
                    <a href="{{ route('observations.create') }}" class="mt-2 inline-block text-sm text-primary-600 hover:text-primary-700">
                        Submit an observation
                    </a>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Performance Chart -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Batch Performance Comparison</h3>
            <div class="flex items-center space-x-2">
                <span class="text-xs text-blue-600"><i class="fas fa-circle mr-1"></i> cFCR</span>
                <span class="text-xs text-green-600"><i class="fas fa-circle mr-1"></i> Profit %</span>
            </div>
        </div>
        <div style="height: 300px;">
            <canvas id="managerPerformanceChart"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('managerPerformanceChart');
    if (ctx) {
        const batchCollection = {!! json_encode($batches ?? []) !!};
        const batchNames = batchCollection.map(batch => batch.batch_id);
        const cfcrData = batchCollection.map(batch => batch.current_cfcr);
        const profitData = batchCollection.map(batch => batch.current_marginal_profit_percent);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: batchNames.length ? batchNames : ['No Data'],
                datasets: [{
                    label: 'cFCR',
                    data: batchNames.length ? cfcrData : [0],
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1,
                    yAxisID: 'y'
                }, {
                    label: 'Profit %',
                    data: batchNames.length ? profitData : [0],
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1,
                    yAxisID: 'y1'
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
                        position: 'left',
                        title: {
                            display: true,
                            text: 'cFCR'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Profit %'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    }
});
</script>
@endpush