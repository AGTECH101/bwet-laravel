@extends('layouts.app')

@section('title', 'Investor Dashboard - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Investor Dashboard</h1>
        <p class="text-sm text-gray-600">Welcome, {{ auth()->user()->name }}. Your investment overview.</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <a href="{{ route('export.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <i class="fas fa-file-export mr-2"></i> Export Report
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Financial KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Investment</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ format_currency($kpis['total_active_investment'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-500 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-500">
                Active: {{ $kpis['active_batches'] ?? 0 }} batches
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ format_currency($kpis['total_estimated_revenue'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-500 flex items-center justify-center">
                    <i class="fas fa-chart-line text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-500">
                {{ $kpis['completed_batches'] ?? 0 }} completed batches
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Profit Margin</p>
                    <p class="text-3xl font-bold {{ ($kpis['overall_profit_margin'] ?? 0) > 0 ? 'text-green-600' : 'text-red-600' }} mt-2">
                        {{ format_percentage($kpis['overall_profit_margin'] ?? 0) }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-purple-500 flex items-center justify-center">
                    <i class="fas fa-percentage text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-sm {{ ($kpis['overall_profit_margin'] ?? 0) > 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ ($kpis['overall_profit_margin'] ?? 0) > 0 ? 'Positive return' : 'Negative return' }}
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">ROI</p>
                    <p class="text-3xl font-bold {{ ($kpis['overall_roi'] ?? 0) > 0 ? 'text-green-600' : 'text-red-600' }} mt-2">
                        {{ format_percentage($kpis['overall_roi'] ?? 0) }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-yellow-500 flex items-center justify-center">
                    <i class="fas fa-arrow-trend-up text-white text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-500">
                Return on Investment
            </div>
        </div>
    </div>

    <!-- Investment Summary -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Investment Summary</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-600">Active Investments</span>
                    <span class="text-sm font-bold text-gray-900">{{ $kpis['active_batches'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-600">Completed Investments</span>
                    <span class="text-sm font-bold text-gray-900">{{ $kpis['completed_batches'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Total Batches</span>
                    <span class="text-sm font-bold text-gray-900">{{ ($kpis['active_batches'] ?? 0) + ($kpis['completed_batches'] ?? 0) }}</span>
                </div>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-600">Total Invested</span>
                    <span class="text-sm font-bold text-gray-900">{{ format_currency($kpis['total_active_investment'] ?? 0) }}</span>
                </div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-600">Total Revenue</span>
                    <span class="text-sm font-bold text-gray-900">{{ format_currency($kpis['total_estimated_revenue'] ?? 0) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Net Profit/Loss</span>
                    <span class="text-sm font-bold {{ (($kpis['total_estimated_revenue'] ?? 0) - ($kpis['total_active_investment'] ?? 0)) > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ format_currency(($kpis['total_estimated_revenue'] ?? 0) - ($kpis['total_active_investment'] ?? 0)) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Investment Portfolio -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">Investment Portfolio</h3>
            <a href="{{ route('poultry.batches.index') }}" class="text-sm text-primary-600 hover:text-primary-700">
                View all batches <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Investment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ROI</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Performance</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($financialData ?? [] as $data)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg {{ $data['status'] == 'active' ? 'bg-blue-100' : 'bg-green-100' }} flex items-center justify-center">
                                    <i class="fas {{ $data['status'] == 'active' ? 'fa-play-circle text-blue-600' : 'fa-check-circle text-green-600' }}"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $data['batch'] }}</div>
                                    <div class="text-xs text-gray-500">{{ ucfirst($data['status']) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $data['status'] == 'active' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                {{ ucfirst($data['status']) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ format_currency($data['total_investment'] ?? 0) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ format_currency($data['estimated_revenue'] ?? 0) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ ($data['profit_margin'] ?? 0) > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ format_currency(($data['estimated_revenue'] ?? 0) - ($data['total_investment'] ?? 0)) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ ($data['profit_margin'] ?? 0) > 20 ? 'bg-green-100 text-green-800' : (($data['profit_margin'] ?? 0) > 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ format_percentage($data['profit_margin'] ?? 0) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full {{ ($data['profit_margin'] ?? 0) > 20 ? 'bg-green-500' : (($data['profit_margin'] ?? 0) > 10 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                    style="width: {{ min(abs($data['profit_margin'] ?? 0), 100) }}%"></div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-chart-line text-2xl mb-2"></i>
                            <p>No investment data available</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Performance Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Performance Metrics</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">Average FCR</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($kpis['avg_fcr'] ?? 0, 3) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ min((($kpis['avg_fcr'] ?? 0) / 2.5) * 100, 100) }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">Mortality Rate</span>
                        <span class="text-sm font-medium text-gray-900">{{ format_percentage($kpis['avg_mortality'] ?? 0) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ min(($kpis['avg_mortality'] ?? 0) * 10, 100) }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">Profit Margin</span>
                        <span class="text-sm font-medium text-gray-900">{{ format_percentage($kpis['overall_profit_margin'] ?? 0) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: {{ min(($kpis['overall_profit_margin'] ?? 0), 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Investment Distribution</h3>
            <div style="height: 250px;">
                <canvas id="investmentDistributionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Thank You Message -->
    <div class="bg-gradient-to-r from-primary-50 to-green-50 rounded-xl border border-primary-200 p-8 text-center">
        <h3 class="text-2xl font-bold text-primary-700">Thank You for Investing in BWET Farms</h3>
        <p class="text-primary-600 mt-2">Your trust and partnership drive our success.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('investmentDistributionChart');
    if (ctx) {
        const active = {{ $kpis['active_batches'] ?? 0 }};
        const completed = {{ $kpis['completed_batches'] ?? 0 }};
        const total = active + completed;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Active Investments', 'Completed Investments'],
                datasets: [{
                    data: [active, completed],
                    backgroundColor: ['#3b82f6', '#10b981'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }
});
</script>
@endpush