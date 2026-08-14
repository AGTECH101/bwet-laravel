@extends('layouts.app')

@section('title', 'Real-Time Charts - ' . $batch->batch_id)

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Real-Time Analytics</h1>
        <p class="text-sm text-gray-600">{{ $batch->batch_id }} - {{ $batch->name }}</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
        <span id="refreshStatus" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium bg-green-100 text-green-800">
            <i class="fas fa-circle text-green-500 mr-2 text-xs"></i> Live
        </span>
        <button onclick="manualRefresh()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            <i class="fas fa-redo mr-2"></i> Refresh
        </button>
        <a href="{{ route('poultry.batches.show', $batch) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
            <div class="text-gray-500 text-sm font-medium">Average Weight</div>
            <div class="text-2xl font-bold text-gray-900 mt-2" id="kpiWeight">--</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
            <div class="text-gray-500 text-sm font-medium">iFCR</div>
            <div class="text-2xl font-bold text-gray-900 mt-2" id="kpiIfcr">--</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
            <div class="text-gray-500 text-sm font-medium">cFCR</div>
            <div class="text-2xl font-bold text-gray-900 mt-2" id="kpiCfcr">--</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
            <div class="text-gray-500 text-sm font-medium">Mortality %</div>
            <div class="text-2xl font-bold text-gray-900 mt-2" id="kpiMortality">--</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
            <div class="text-gray-500 text-sm font-medium">ADG</div>
            <div class="text-2xl font-bold text-gray-900 mt-2" id="kpiAdg">--</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Weight Progression</h3>
            <div style="height: 300px;"><canvas id="weightChart"></canvas></div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">FCR Over Time</h3>
            <div style="height: 300px;"><canvas id="fcrChart"></canvas></div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Historical Data</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Weight</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">iFCR</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">cFCR</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mortality %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ADG</th>
                    </tr>
                </thead>
                <tbody id="dataTableBody">
                    <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">Loading data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const batchId = '{{ $batch->id }}';
let weightChart, fcrChart;
let refreshInterval;

document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    fetchData();
    startAutoRefresh();
});

function initializeCharts() {
    const weightCtx = document.getElementById('weightChart').getContext('2d');
    weightChart = new Chart(weightCtx, {
        type: 'line',
        data: { labels: [], datasets: [{ label: 'Weight (kg)', data: [], borderColor: '#3b82f6', tension: 0.4, fill: true }] },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
    });

    const fcrCtx = document.getElementById('fcrChart').getContext('2d');
    fcrChart = new Chart(fcrCtx, {
        type: 'line',
        data: { labels: [], datasets: [
            { label: 'iFCR', data: [], borderColor: '#f59e0b', tension: 0.4 },
            { label: 'cFCR', data: [], borderColor: '#ef4444', tension: 0.4 }
        ]},
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
    });
}

function fetchData() {
    fetch(`/api/batches/${batchId}/chart-data/`)
        .then(r => r.json())
        .then(data => {
            updateCharts(data);
            updateKPIs(data);
            updateTable(data);
            updateRefreshStatus('success');
        })
        .catch(() => updateRefreshStatus('error'));
}

function updateCharts(data) {
    if (data.weight_data) {
        weightChart.data.labels = data.weight_data.labels || [];
        weightChart.data.datasets[0].data = data.weight_data.values || [];
        weightChart.update();
    }
    if (data.fcr_data) {
        fcrChart.data.labels = data.fcr_data.labels || [];
        fcrChart.data.datasets[0].data = data.fcr_data.ifcr_values || [];
        fcrChart.data.datasets[1].data = data.fcr_data.cfcr_values || [];
        fcrChart.update();
    }
}

function updateKPIs(data) {
    document.getElementById('kpiWeight').textContent = data.current_weight ? data.current_weight.toFixed(3) + ' kg' : '--';
    document.getElementById('kpiIfcr').textContent = data.current_ifcr ? data.current_ifcr.toFixed(3) : '--';
    document.getElementById('kpiCfcr').textContent = data.current_cfcr ? data.current_cfcr.toFixed(3) : '--';
    document.getElementById('kpiMortality').textContent = data.current_mortality ? data.current_mortality.toFixed(2) + '%' : '--';
    document.getElementById('kpiAdg').textContent = data.current_adg ? data.current_adg.toFixed(3) : '--';
}

function updateTable(data) {
    const tbody = document.getElementById('dataTableBody');
    if (!data.historical_data || !data.historical_data.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No data available</td></tr>';
        return;
    }
    tbody.innerHTML = data.historical_data.map(row => `
        <tr>
            <td class="px-6 py-3 text-sm">${row.date || '-'}</td>
            <td class="px-6 py-3 text-sm">${row.age || '-'}</td>
            <td class="px-6 py-3 text-sm">${row.weight ? row.weight.toFixed(3) : '-'}</td>
            <td class="px-6 py-3 text-sm">${row.ifcr ? row.ifcr.toFixed(3) : '-'}</td>
            <td class="px-6 py-3 text-sm">${row.cfcr ? row.cfcr.toFixed(3) : '-'}</td>
            <td class="px-6 py-3 text-sm">${row.mortality ? row.mortality.toFixed(2) + '%' : '-'}</td>
            <td class="px-6 py-3 text-sm">${row.adg ? row.adg.toFixed(3) : '-'}</td>
        </tr>
    `).join('');
}

function updateRefreshStatus(status) {
    const el = document.getElementById('refreshStatus');
    if (status === 'success') {
        el.innerHTML = '<i class="fas fa-circle text-green-500 mr-2 text-xs"></i> Live (updated)';
        setTimeout(() => el.innerHTML = '<i class="fas fa-circle text-green-500 mr-2 text-xs"></i> Live', 2000);
    } else {
        el.innerHTML = '<i class="fas fa-circle text-red-500 mr-2 text-xs"></i> Error updating';
    }
}

function startAutoRefresh() {
    refreshInterval = setInterval(fetchData, 30000);
}

function manualRefresh() {
    fetchData();
}

// Clean up interval on page leave
document.addEventListener('beforeunload', function() {
    if (refreshInterval) clearInterval(refreshInterval);
});
</script>
@endpush
@endsection