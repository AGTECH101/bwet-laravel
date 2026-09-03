@extends('layouts.app')

@section('title', $batch->batch_id . ' - ' . $batch->name)

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            {{ $batch->batch_id }} - {{ $batch->name }}
        </h1>
        <div class="flex items-center space-x-4 mt-2">
            <span class="text-sm text-gray-600"><i class="fas fa-calendar-alt mr-1"></i> Started {{ $batch->start_date->format('M d, Y') }}</span>
            <span class="text-sm text-gray-600"><i class="fas fa-clock mr-1"></i> {{ $batch->age_days }} days old</span>
            {!! batch_status_badge($batch->status) !!}
            @if($batch->is_manual_mode)
            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                <i class="fas fa-user-cog mr-1"></i> Manual Mode
            </span>
            @endif
        </div>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
        <a href="{{ route('poultry.batches.export', $batch) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-file-export mr-2"></i> Export
        </a>
        <a href="{{ route('poultry.forms.hub') }}?batch={{ $batch->batch_id }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            <i class="fas fa-plus mr-2"></i> Add Record
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg shadow p-4 border-l-4 border-blue-500">
            <h3 class="text-sm font-medium text-blue-700"><i class="fas fa-hourglass-half mr-1"></i>Age</h3>
            <p class="text-2xl font-bold text-blue-900 mt-2">{{ $batch->age_days }} <span class="text-sm">days</span></p>
        </div>
        @if(auth()->user()->role === 'admin')
        <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-lg shadow p-4 border-l-4 border-emerald-500">
            <h3 class="text-sm font-medium text-emerald-700"><i class="fas fa-coins mr-1"></i>Cost/Bird</h3>
            <p class="text-2xl font-bold text-emerald-900 mt-2">{{ format_currency($financialMetrics['cost_per_bird'] ?? 0) }}</p>
        </div>
        <div class="bg-gradient-to-br from-cyan-50 to-cyan-100 rounded-lg shadow p-4 border-l-4 border-cyan-500">
            <h3 class="text-sm font-medium text-cyan-700"><i class="fas fa-weight mr-1"></i>Cost/Kg</h3>
            <p class="text-2xl font-bold text-cyan-900 mt-2">{{ format_currency($financialMetrics['cost_per_kg'] ?? 0) }}</p>
        </div>
        @endif
        <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-lg shadow p-4 border-l-4 border-amber-500">
            <h3 class="text-sm font-medium text-amber-700"><i class="fas fa-kiwi-bird mr-1"></i>{{ auth()->user()->role === 'admin' ? 'Sell/Bird' : 'Recommended Sell/Bird' }}</h3>
            <p class="text-2xl font-bold text-amber-900 mt-2">{{ format_currency($financialMetrics['selling_price_per_bird'] ?? 0) }}</p>
        </div>
        <div class="bg-gradient-to-br from-rose-50 to-rose-100 rounded-lg shadow p-4 border-l-4 border-rose-500">
            <h3 class="text-sm font-medium text-rose-700"><i class="fas fa-tag mr-1"></i>{{ auth()->user()->role === 'admin' ? 'Sell/Kg' : 'Recommended Sell/Kg' }}</h3>
            <p class="text-2xl font-bold text-rose-900 mt-2">{{ format_currency($financialMetrics['selling_price_per_kg'] ?? 0) }}</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-medium text-gray-900 mb-4">Flock Status</h3>
            <div class="space-y-3">
                <div class="flex justify-between"><span class="text-sm text-gray-600">Remaining</span><span class="text-lg font-bold">{{ $batch->remaining_flock }}</span></div>
                <div class="flex justify-between"><span class="text-sm text-gray-600">Starting</span><span class="text-lg font-bold">{{ $batch->starting_flock }}</span></div>
                <div class="pt-3 border-t border-gray-100">
                    <div class="flex justify-between text-sm">
                        <span class="text-red-600">Mortality: {{ number_format($batch->total_mortality, 1) }}</span>
                        <span class="text-yellow-600">Culls: {{ $batch->total_culls }}</span>
                        <span class="text-green-600">Slaughter: {{ $batch->total_slaughter }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-medium text-gray-900 mb-4">Performance</h3>
            <div class="space-y-3">
                <div class="flex justify-between"><span class="text-sm text-gray-600">Current Weight</span><span class="text-lg font-bold">{{ format_weight($batch->getCurrentAverageWeight()) }}</span></div>
                <div class="flex justify-between"><span class="text-sm text-gray-600">iFCR</span><span class="text-lg font-bold {{ $batch->current_ifcr < 1.8 ? 'text-green-600' : ($batch->current_ifcr < 2.0 ? 'text-yellow-600' : 'text-red-600') }}">{{ format_fcr($batch->current_ifcr) }}</span></div>
                <div class="flex justify-between"><span class="text-sm text-gray-600">cFCR</span><span class="text-lg font-bold {{ $batch->current_cfcr < 1.8 ? 'text-green-600' : ($batch->current_cfcr < 2.0 ? 'text-yellow-600' : 'text-red-600') }}">{{ format_fcr($batch->current_cfcr) }}</span></div>
            </div>
        </div>
    </div>

    <!-- Mortality Overview (New) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Mortality Overview</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-500">Total Mortality</p>
                <p class="text-xl font-bold text-red-600">{{ number_format($batch->total_mortality, 1) }}</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-500">Mortality Rate</p>
                <p class="text-xl font-bold {{ $batch->mortality_rate > 7 ? 'text-red-600' : 'text-yellow-600' }}">
                    {{ number_format($batch->mortality_rate, 1) }}%
                </p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-500">Pen Mortality</p>
                <p class="text-xl font-bold text-orange-600">{{ number_format($batch->pen_mortality, 1) }}</p>
            </div>
        </div>
        <p class="mt-3 text-xs text-gray-500">
            <span class="text-blue-600">Historical</span> = deaths that traveled with transferred birds &bull;
            <span class="text-orange-600">pen</span> = deaths that occurred in this pen &bull;
            <span class="text-red-600">Total</span> = Historical + pen
        </p>
    </div>

    <!-- Slaughter Triggers -->
    @if(count($slaughterTriggers) > 0)
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <h4 class="text-sm font-semibold text-red-800 mb-3"><i class="fas fa-exclamation-triangle mr-2"></i>Slaughter Triggers</h4>
        <div class="space-y-2">
            @foreach($slaughterTriggers as $trigger)
            <div class="flex items-start p-2 bg-white rounded border border-red-100">
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                    {{ $trigger['severity'] == 'critical' ? 'bg-red-100 text-red-800' :
                       ($trigger['severity'] == 'warning' ? 'bg-yellow-100 text-yellow-800' :
                       'bg-blue-100 text-blue-800') }}">
                    {{ ucfirst($trigger['severity']) }}
                </span>
                <p class="ml-3 text-sm text-gray-700">{{ $trigger['message'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Charts -->
    @if(!empty($chartData) && !$chartData['no_data'])
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Growth Chart</h3>
            <div class="flex items-center space-x-2">
                <span class="text-xs text-blue-600"><i class="fas fa-circle mr-1"></i> Weight</span>
                <span class="text-xs text-green-600"><i class="fas fa-circle mr-1"></i> Target</span>
            </div>
        </div>
        <div style="height: 300px;">
            <canvas id="batchWeightChart"></canvas>
        </div>
    </div>
    @endif

    <!-- Tabs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px" id="batchTabs">
                <button type="button" data-tab="weight" class="tab-button whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm border-primary-500 text-primary-600">Weight</button>
                <button type="button" data-tab="feed" class="tab-button whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">Feed</button>
                <button type="button" data-tab="expenses" class="tab-button whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">Expenses</button>
                <button type="button" data-tab="flock" class="tab-button whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">Flock</button>
            </nav>
        </div>
        <div class="p-6">
            <!-- Weight Tab -->
            <div id="weight-tab" class="tab-content active">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Date</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Sample</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Avg. Weight</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">CV</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th></tr></thead>
                        <tbody>
                            @forelse($recentWeight as $record)
                            <tr><td class="px-4 py-2 text-sm">{{ $record->date->format('M d, Y') }}</td><td class="px-4 py-2 text-sm">{{ $record->birds_weighed }}</td><td class="px-4 py-2 text-sm font-medium">{{ format_weight($record->average_weight) }}</td><td class="px-4 py-2 text-sm">{{ $record->coefficient_variation }}%</td><td class="px-4 py-2">{!! cv_status_badge($record->cv_status) !!}</td></tr>
                            @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No weight records</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Feed Tab -->
            <div id="feed-tab" class="tab-content hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Date</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Feed Used</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Cost/kg</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Total Cost</th></tr></thead>
                        <tbody>
                            @forelse($recentFeed as $record)
                            <tr><td class="px-4 py-2 text-sm">{{ $record->date->format('M d, Y') }}</td><td class="px-4 py-2 text-sm">{{ number_format($record->feed_used, 2) }} kg</td><td class="px-4 py-2 text-sm">{{ format_currency($record->feed_cost_per_kg) }}</td><td class="px-4 py-2 text-sm font-medium">{{ format_currency($record->total_feed_cost) }}</td></tr>
                            @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No feed records</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Expenses Tab -->
            <div id="expenses-tab" class="tab-content hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Date</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Category</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Description</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Amount</th></tr></thead>
                        <tbody>
                            @forelse($recentExpenses as $expense)
                            <tr><td class="px-4 py-2 text-sm">{{ $expense->date->format('M d, Y') }}</td><td class="px-4 py-2 text-sm">{{ ucfirst($expense->category) }}</td><td class="px-4 py-2 text-sm">{{ $expense->description }}</td><td class="px-4 py-2 text-sm font-medium">{{ format_currency($expense->amount) }}</td></tr>
                            @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No expenses</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Flock Tab -->
            <div id="flock-tab" class="tab-content hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Date</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Mortality</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Culls</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Slaughter</th></tr></thead>
                        <tbody>
                            @forelse($recentFlock as $record)
                            <tr><td class="px-4 py-2 text-sm">{{ $record->date->format('M d, Y') }}</td><td class="px-4 py-2 text-sm text-red-600">{{ $record->mortality }}</td><td class="px-4 py-2 text-sm text-yellow-600">{{ $record->culls }}</td><td class="px-4 py-2 text-sm text-green-600">{{ $record->slaughter }}</td></tr>
                            @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No flock records</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4"><i class="fas fa-weight text-blue-600 text-xl"></i></div>
            <h4 class="text-lg font-semibold text-gray-900 mb-2">Record Weight</h4>
            <a href="{{ route('poultry.forms.weight-record.create', ['batch' => $batch->batch_id]) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"><i class="fas fa-plus mr-2"></i> Add Weight</a>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4"><i class="fas fa-utensils text-green-600 text-xl"></i></div>
            <h4 class="text-lg font-semibold text-gray-900 mb-2">Record Feed</h4>
            <a href="{{ route('poultry.forms.feed-record.create', ['batch' => $batch->batch_id]) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"><i class="fas fa-plus mr-2"></i> Add Feed</a>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
            <div class="w-16 h-16 rounded-full bg-purple-100 flex items-center justify-center mx-auto mb-4"><i class="fas fa-kiwi-bird text-purple-600 text-xl"></i></div>
            <h4 class="text-lg font-semibold text-gray-900 mb-2">Update Flock</h4>
            <a href="{{ route('poultry.forms.flock-record.create', ['batch' => $batch->batch_id]) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"><i class="fas fa-plus mr-2"></i> Update Flock</a>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tabs
    const tabs = document.querySelectorAll('.tab-button');
    const contents = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('border-primary-500', 'text-primary-600'));
            this.classList.add('border-primary-500', 'text-primary-600');
            const target = this.dataset.tab;
            contents.forEach(c => c.classList.add('hidden'));
            document.getElementById(target + '-tab').classList.remove('hidden');
        });
    });

    // Chart
    @if(!empty($chartData) && !$chartData['no_data'])
    const ctx = document.getElementById('batchWeightChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartData['age_vs_weight']['ages'] ?? []) !!},
                datasets: [{
                    label: 'Weight (kg)',
                    data: {!! json_encode($chartData['age_vs_weight']['weights'] ?? []) !!},
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Target Weight',
                    data: {!! json_encode(array_fill(0, count($chartData['age_vs_weight']['ages'] ?? []), 2.2)) !!},
                    borderColor: '#10b981',
                    borderDash: [5,5],
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, title: { display: true, text: 'Weight (kg)' } } },
                plugins: { legend: { position: 'top' } }
            }
        });
    }
    @endif
});
</script>
@endpush
@endsection