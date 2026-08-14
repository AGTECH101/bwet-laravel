@extends('layouts.app')

@section('title', 'Global Analytics - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Global Analytics</h1>
        <p class="text-sm text-gray-600">Comprehensive analytics and performance metrics across all batches</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
        <a href="{{ route('export.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            <i class="fas fa-file-export mr-2"></i> Export Report
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div><p class="text-sm font-medium text-gray-600">Total Batches</p><p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalBatches ?? 0 }}</p></div>
                <div class="w-12 h-12 rounded-lg bg-primary-500 flex items-center justify-center"><i class="fas fa-layer-group text-white text-xl"></i></div>
            </div>
            <div class="mt-4 text-sm"><span class="text-green-600">{{ $activeBatches ?? 0 }} active</span><span class="ml-4 text-gray-500">{{ $completedBatches ?? 0 }} completed</span></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div><p class="text-sm font-medium text-gray-600">Total Investment</p><p class="text-3xl font-bold text-gray-900 mt-2">{{ format_currency($totalInvestment ?? 0) }}</p></div>
                <div class="w-12 h-12 rounded-lg bg-green-500 flex items-center justify-center"><i class="fas fa-money-bill-wave text-white text-xl"></i></div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div><p class="text-sm font-medium text-gray-600">Avg. FCR</p><p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($avgFcr ?? 0, 3) }}</p></div>
                <div class="w-12 h-12 rounded-lg bg-blue-500 flex items-center justify-center"><i class="fas fa-chart-line text-white text-xl"></i></div>
            </div>
            <div class="mt-4 text-sm {{ ($avgFcr ?? 0) < 1.8 ? 'text-green-600' : (($avgFcr ?? 0) < 2.0 ? 'text-yellow-600' : 'text-red-600') }}">
                {{ ($avgFcr ?? 0) < 1.8 ? 'Excellent' : (($avgFcr ?? 0) < 2.0 ? 'Good' : 'Needs Improvement') }}
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div><p class="text-sm font-medium text-gray-600">Avg. Mortality</p><p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($avgMortality ?? 0, 1) }}%</p></div>
                <div class="w-12 h-12 rounded-lg {{ ($avgMortality ?? 0) < 5 ? 'bg-green-500' : (($avgMortality ?? 0) < 8 ? 'bg-yellow-500' : 'bg-red-500') }} flex items-center justify-center">
                    <i class="fas fa-chart-pie text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-500">Target: < 5%</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">FCR Trend Over Time</h3>
            <div style="height: 300px;"><canvas id="fcrTrendChart"></canvas></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Mortality Distribution</h3>
            <div style="height: 300px;"><canvas id="mortalityChart"></canvas></div>
        </div>
    </div>

    <!-- Performance Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Recent Batch Performance</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">FCR</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mortality</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profit %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Performance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPerformance ?? [] as $perf)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $perf['batch'] ?? 'N/A' }}</td>
                        <td class="px-6 py-4">{!! batch_status_badge($perf['status'] ?? 'unknown') !!}</td>
                        <td class="px-6 py-4 text-sm">{{ $perf['age'] ?? 0 }} days</td>
                        <td class="px-6 py-4 text-sm {{ ($perf['ifcr'] ?? 0) < 1.8 ? 'text-green-600' : 'text-yellow-600' }}">{{ number_format($perf['ifcr'] ?? 0, 3) }}</td>
                        <td class="px-6 py-4 text-sm {{ ($perf['mortality'] ?? 0) < 5 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($perf['mortality'] ?? 0, 1) }}%</td>
                        <td class="px-6 py-4 text-sm {{ ($perf['profit_percent'] ?? 0) > 20 ? 'text-green-600' : 'text-yellow-600' }}">{{ number_format($perf['profit_percent'] ?? 0, 1) }}%</td>
                        <td class="px-6 py-4">
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full {{ (($perf['profit_percent'] ?? 0) + 20) / 2 > 70 ? 'bg-green-500' : (($perf['profit_percent'] ?? 0) + 20) / 2 > 50 ? 'bg-yellow-500' : 'bg-red-500' }}"
                                     style="width: {{ min((($perf['profit_percent'] ?? 0) + 20) / 2, 100) }}%"></div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">No performance data available</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Insights -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Performers</h3>
            @forelse($topPerformers ?? [] as $performer)
            <div class="p-3 bg-green-50 rounded-lg border border-green-100 mb-2">
                <p class="text-sm font-medium text-gray-900">{{ $performer['batch'] }}</p>
                <p class="text-xs text-green-600">Profit: {{ number_format($performer['profit_percent'] ?? 0, 1) }}%</p>
            </div>
            @empty
            <p class="text-sm text-gray-500">No top performers</p>
            @endforelse
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Areas for Improvement</h3>
            @forelse($improvementAreas ?? [] as $area)
            <div class="p-3 bg-red-50 rounded-lg border border-red-100 mb-2">
                <p class="text-sm font-medium text-gray-900">{{ $area['batch'] }}</p>
                <p class="text-xs text-red-600">{{ $area['issue'] }}</p>
            </div>
            @empty
            <p class="text-sm text-gray-500">All batches performing well</p>
            @endforelse
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recommendations</h3>
            <ul class="space-y-2 text-sm text-gray-700">
                @if(($avgMortality ?? 0) > 5)
                <li class="flex items-start"><i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5 mr-2"></i> Review biosecurity measures</li>
                @endif
                @if(($avgFcr ?? 0) > 1.9)
                <li class="flex items-start"><i class="fas fa-utensils text-blue-500 mt-0.5 mr-2"></i> Consider feed quality optimization</li>
                @endif
                <li class="flex items-start"><i class="fas fa-chart-line text-green-500 mt-0.5 mr-2"></i> Regular weight monitoring improves FCR</li>
                <li class="flex items-start"><i class="fas fa-temperature-high text-red-500 mt-0.5 mr-2"></i> Monitor temperature during extreme weather</li>
            </ul>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fcrCtx = document.getElementById('fcrTrendChart');
    if (fcrCtx) {
        new Chart(fcrCtx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6', 'Week 7', 'Week 8'],
                datasets: [
                    { label: 'Average iFCR', data: [1.2, 1.4, 1.6, 1.7, 1.8, 1.85, 1.9, 1.95], borderColor: '#3b82f6', tension: 0.4 },
                    { label: 'Target FCR', data: [1.8, 1.8, 1.8, 1.8, 1.8, 1.8, 1.8, 1.8], borderColor: '#10b981', borderDash: [5,5], pointRadius: 0 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: false, min: 1.0 } } }
        });
    }

    const mortalityCtx = document.getElementById('mortalityChart');
    if (mortalityCtx) {
        new Chart(mortalityCtx, {
            type: 'bar',
            data: {
                labels: ['< 3%', '3-5%', '5-8%', '8-10%', '> 10%'],
                datasets: [{ label: 'Number of Batches', data: [5, 8, 4, 2, 1], backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#f97316', '#ef4444'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    }
});
</script>
@endpush
@endsection