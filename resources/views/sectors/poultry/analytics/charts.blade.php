@extends('layouts.app')

@section('title', 'Batch Charts - ' . $batch->batch_id)

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Batch Analytics</h1>
        <p class="text-sm text-gray-600">{{ $batch->batch_id }} - {{ $batch->name }}</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
        <a href="{{ route('poultry.batches.show', $batch) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
        <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            <i class="fas fa-print mr-2"></i> Print Charts
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-600">Age</p>
            <p class="text-2xl font-bold text-gray-900">{{ $batch->current_age_days }} <span class="text-sm font-normal text-gray-500">days</span></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-600">Remaining Flock</p>
            <p class="text-2xl font-bold text-gray-900">{{ $batch->remaining_flock }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-600">Avg. Weight</p>
            <p class="text-2xl font-bold text-gray-900">{{ format_weight($batch->getCurrentAverageWeight()) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-600">cFCR</p>
            <p class="text-2xl font-bold text-gray-900">{{ format_fcr($batch->current_cfcr) }}</p>
        </div>
    </div>

    <!-- Main Chart -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">FCR Analysis</h3>
            <div class="flex items-center space-x-4">
                <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-blue-500 mr-2"></span><span class="text-xs text-gray-600">iFCR</span></div>
                <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-red-500 mr-2"></span><span class="text-xs text-gray-600">cFCR</span></div>
            </div>
        </div>
        <div style="height: 400px;">
            <canvas id="fcrChart"></canvas>
        </div>
    </div>

    <!-- Secondary Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Weight Growth</h3>
            <div style="height: 300px;">
                <canvas id="weightChart"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Average Daily Gain</h3>
            <div style="height: 300px;">
                <canvas id="adgChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">Chart Data</h3>
            <button onclick="exportData()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-download mr-2"></i> Export CSV
            </button>
        </div>
        <div class="overflow-x-auto p-4">
            <table class="min-w-full divide-y divide-gray-200" id="chartDataTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Weight</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">iFCR</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">cFCR</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ADG</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($chartData['metrics'] ?? [] as $metric)
                    <tr>
                        <td class="px-4 py-2 text-sm">{{ $metric['date'] }}</td>
                        <td class="px-4 py-2 text-sm">{{ $metric['age_days'] }}</td>
                        <td class="px-4 py-2 text-sm">{{ number_format($metric['average_weight'], 3) }}</td>
                        <td class="px-4 py-2 text-sm">{{ number_format($metric['ifcr'], 3) }}</td>
                        <td class="px-4 py-2 text-sm">{{ number_format($metric['cfcr'], 3) }}</td>
                        <td class="px-4 py-2 text-sm">{{ number_format($metric['adg'], 3) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No data available</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartData = @json($chartData);

    // FCR Chart
    if (chartData.ifcr_vs_cfcr && chartData.ifcr_vs_cfcr.labels) {
        const fcrCtx = document.getElementById('fcrChart').getContext('2d');
        new Chart(fcrCtx, {
            type: 'line',
            data: {
                labels: chartData.ifcr_vs_cfcr.labels || [],
                datasets: [
                    { label: 'iFCR', data: chartData.ifcr_vs_cfcr.ifcr || [], borderColor: '#3b82f6', tension: 0.4 },
                    { label: 'cFCR', data: chartData.ifcr_vs_cfcr.cfcr || [], borderColor: '#ef4444', tension: 0.4 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });
    }

    // Weight Chart
    if (chartData.age_vs_weight && chartData.age_vs_weight.ages) {
        const weightCtx = document.getElementById('weightChart').getContext('2d');
        new Chart(weightCtx, {
            type: 'line',
            data: {
                labels: chartData.age_vs_weight.ages || [],
                datasets: [{ label: 'Weight (kg)', data: chartData.age_vs_weight.weights || [], borderColor: '#10b981', tension: 0.4, fill: true }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });
    }

    // ADG Chart
    if (chartData.adg_vs_age && chartData.adg_vs_age.dates) {
        const adgCtx = document.getElementById('adgChart').getContext('2d');
        new Chart(adgCtx, {
            type: 'line',
            data: {
                labels: chartData.adg_vs_age.dates || [],
                datasets: [
                    { label: 'ADG (kg/day)', data: chartData.adg_vs_age.adg || [], borderColor: '#8b5cf6', tension: 0.4 },
                    { label: 'Target ADG', data: chartData.adg_vs_age.target_adg || [], borderColor: '#f59e0b', borderDash: [5,5], pointRadius: 0 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });
    }
});

function exportData() {
    const rows = document.querySelectorAll('#chartDataTable tbody tr');
    let csv = 'Date,Age,Weight,iFCR,cFCR,ADG\n';
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length === 6) {
            csv += Array.from(cells).map(c => c.textContent.trim()).join(',') + '\n';
        }
    });
    const blob = new Blob([csv], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'batch_data.csv';
    link.click();
}
</script>
@endpush
@endsection