@extends('layouts.app')

@section('title', 'Export Data - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Export Data</h1>
        <p class="text-sm text-gray-600">Export your data in various formats for analysis and reporting</p>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Export Form -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Export Settings</h3>
            <form method="POST" action="{{ route('export.run') }}" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Export Type</label>
                    <select name="export_type" id="exportType" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="batch">Single Batch</option>
                        <option value="database">Full Database</option>
                        <option value="analytics">Analytics Report</option>
                        <option value="financial">Financial Summary</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Export Format</label>
                    <select name="format" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="excel">Excel (.xlsx)</option>
                        <option value="csv">CSV</option>
                        <option value="pdf">PDF</option>
                    </select>
                </div>

                <div id="batchSelection" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Batch</label>
                    <select name="batch_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="">-- Select Batch --</option>
                        @foreach($batches ?? [] as $batch)
                        <option value="{{ $batch->id }}">{{ $batch->batch_id }} - {{ $batch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                        <input type="date" name="date_from" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                        <input type="date" name="date_to" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="include_charts" id="include_charts" value="1" checked class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="include_charts" class="ml-2 text-sm text-gray-700">Include charts in export</label>
                </div>

                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                        <i class="fas fa-file-export mr-2"></i> Export Data
                    </button>
                </div>
            </form>
        </div>

        <!-- Export History -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Export History</h3>
            @if(isset($exportHistory) && count($exportHistory) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Format</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">File</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($exportHistory as $export)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $export->created_at->format('M d, Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ ucfirst($export->export_type) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ strtoupper($export->export_format) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ Str::limit($export->file_name, 30) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i> Completed
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-sm text-gray-500 text-center py-4">No export history yet</p>
            @endif
        </div>
    </div>

    <!-- Quick Export Options -->
    <div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Quick Export</h3>
            <div class="space-y-4">
                <a href="{{ route('export.run') }}?quick=all_batches" class="block p-4 bg-primary-50 hover:bg-primary-100 rounded-xl border border-primary-200 transition-all group">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center"><i class="fas fa-layer-group text-primary-600"></i></div>
                        <div class="ml-4"><h4 class="text-sm font-medium text-gray-900">All Batches</h4><p class="text-xs text-gray-600">Complete batch data</p></div>
                    </div>
                </a>
                <a href="{{ route('export.run') }}?quick=current_month" class="block p-4 bg-green-50 hover:bg-green-100 rounded-xl border border-green-200 transition-all">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center"><i class="fas fa-calendar-alt text-green-600"></i></div>
                        <div class="ml-4"><h4 class="text-sm font-medium text-gray-900">Current Month</h4><p class="text-xs text-gray-600">This month's records</p></div>
                    </div>
                </a>
                <a href="{{ route('export.run') }}?quick=performance" class="block p-4 bg-purple-50 hover:bg-purple-100 rounded-xl border border-purple-200 transition-all">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center"><i class="fas fa-chart-line text-purple-600"></i></div>
                        <div class="ml-4"><h4 class="text-sm font-medium text-gray-900">Performance Report</h4><p class="text-xs text-gray-600">FCR and growth metrics</p></div>
                    </div>
                </a>
                <a href="{{ route('export.run') }}?quick=financial" class="block p-4 bg-yellow-50 hover:bg-yellow-100 rounded-xl border border-yellow-200 transition-all">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-lg bg-yellow-100 flex items-center justify-center"><i class="fas fa-money-bill-wave text-yellow-600"></i></div>
                        <div class="ml-4"><h4 class="text-sm font-medium text-gray-900">Financial Summary</h4><p class="text-xs text-gray-600">Costs, revenue, and profits</p></div>
                    </div>
                </a>
                <a href="{{ route('export.run') }}?quick=inventory" class="block p-4 bg-red-50 hover:bg-red-100 rounded-xl border border-red-200 transition-all">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center"><i class="fas fa-boxes text-red-600"></i></div>
                        <div class="ml-4"><h4 class="text-sm font-medium text-gray-900">Inventory Report</h4><p class="text-xs text-gray-600">Stock levels and usage</p></div>
                    </div>
                </a>
            </div>

            <div class="mt-6 p-4 bg-gray-50 rounded-xl">
                <h4 class="text-sm font-medium text-gray-900 mb-3"><i class="fas fa-info-circle mr-2"></i>Export Information</h4>
                <ul class="space-y-2 text-xs text-gray-600">
                    <li class="flex items-start"><i class="fas fa-check text-green-500 mt-0.5 mr-2"></i>All exports include timestamps</li>
                    <li class="flex items-start"><i class="fas fa-check text-green-500 mt-0.5 mr-2"></i>Excel files contain multiple sheets</li>
                    <li class="flex items-start"><i class="fas fa-check text-green-500 mt-0.5 mr-2"></i>Data is formatted for analysis</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const exportType = document.getElementById('exportType');
    const batchSelection = document.getElementById('batchSelection');

    exportType.addEventListener('change', function() {
        batchSelection.style.display = this.value === 'batch' ? 'block' : 'none';
    });
});
</script>
@endpush
@endsection