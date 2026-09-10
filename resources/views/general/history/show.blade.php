@extends('layouts.app')

@section('title', $query->name . ' - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $query->name ?: 'Saved Query' }}</h1>
        <p class="text-sm text-gray-600">
            {{ ucfirst($query->query_type) }} •
            @if($query->date_from && $query->date_to)
                {{ \Carbon\Carbon::parse($query->date_from)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($query->date_to)->format('M d, Y') }}
            @else
                All dates
            @endif
        </p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
        <a href="{{ route('history.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
        <button onclick="exportResults()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            <i class="fas fa-download mr-2"></i> Export
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Query Details -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-primary-50 to-primary-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-lg bg-white flex items-center justify-center shadow-sm">
                        <i class="fas fa-search text-primary-600"></i>
                    </div>
                    <div class="ml-4">
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600"><i class="far fa-user mr-1"></i> Created by {{ $query->createdBy->name }}</span>
                            <span class="text-sm text-gray-600"><i class="far fa-clock mr-1"></i> {{ $query->created_at->format('M d, Y') }}</span>
                            @if($query->last_executed)
                            <span class="text-sm text-gray-600"><i class="fas fa-sync-alt mr-1"></i> Last run {{ $query->last_executed->diffForHumans() }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        <i class="fas fa-filter mr-1"></i> {{ ucfirst($query->query_type) }}
                    </span>
                    @if($query->is_shared)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <i class="fas fa-share-alt mr-1"></i> Shared
                    </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center mb-2"><i class="fas fa-calendar-alt text-gray-400 mr-2"></i><span class="text-sm font-medium text-gray-700">Date Range</span></div>
                    <p class="text-sm text-gray-600">
                        @if($query->date_from && $query->date_to)
                            {{ \Carbon\Carbon::parse($query->date_from)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($query->date_to)->format('M d, Y') }}
                        @else
                            All dates
                        @endif
                    </p>
                </div>
                @if($query->user_filter)
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center mb-2"><i class="fas fa-user text-gray-400 mr-2"></i><span class="text-sm font-medium text-gray-700">User Filter</span></div>
                    <p class="text-sm text-gray-600">{{ $query->userFilter->name }}</p>
                </div>
                @endif
                @if($query->batch_filter)
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center mb-2"><i class="fas fa-layer-group text-gray-400 mr-2"></i><span class="text-sm font-medium text-gray-700">Batch Filter</span></div>
                    <p class="text-sm text-gray-600">{{ $query->batchFilter->batch_id }}</p>
                </div>
                @endif
                @if($query->category_filter)
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center mb-2"><i class="fas fa-folder text-gray-400 mr-2"></i><span class="text-sm font-medium text-gray-700">Category</span></div>
                    <p class="text-sm text-gray-600">{{ $query->category_filter }}</p>
                </div>
                @endif
                @if($query->min_amount || $query->max_amount)
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center mb-2"><i class="fas fa-money-bill-wave text-gray-400 mr-2"></i><span class="text-sm font-medium text-gray-700">Amount Range</span></div>
                    <p class="text-sm text-gray-600">
                        @if($query->min_amount && $query->max_amount)
                            {{ format_currency($query->min_amount) }} to {{ format_currency($query->max_amount) }}
                        @elseif($query->min_amount)
                            Min: {{ format_currency($query->min_amount) }}
                        @elseif($query->max_amount)
                            Max: {{ format_currency($query->max_amount) }}
                        @endif
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Results Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <div class="flex items-center">
                <h3 class="text-lg font-semibold text-gray-900">Query Results</h3>
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                    {{ count($results) }} records
                </span>
            </div>
            <div class="flex items-center space-x-2">
                <input type="text" id="searchResults" placeholder="Search in results..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <select id="sortResults" class="pl-4 pr-8 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="date_desc">Date (Newest First)</option>
                    <option value="date_asc">Date (Oldest First)</option>
                    <option value="amount_desc">Amount (High to Low)</option>
                    <option value="amount_asc">Amount (Low to High)</option>
                </select>
            </div>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="resultsTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($results as $row)
                        <tr class="result-row hover:bg-gray-50" data-type="{{ $row['type'] }}" data-date="{{ $row['date'] }}" data-amount="{{ $row['amount'] ?? 0 }}">
                            <td class="px-4 py-2 text-sm">{{ \Carbon\Carbon::parse($row['date'])->format('M d, Y') }}</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                    {{ $row['type'] == 'expense' ? 'bg-red-100 text-red-800' :
                                       ($row['type'] == 'feed' ? 'bg-yellow-100 text-yellow-800' :
                                       ($row['type'] == 'weight' ? 'bg-blue-100 text-blue-800' :
                                       ($row['type'] == 'flock' ? 'bg-green-100 text-green-800' :
                                       'bg-gray-100 text-gray-800'))) }}">
                                    {{ ucfirst($row['type']) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm max-w-xs truncate">{{ Str::limit($row['description'] ?? '', 60) }}</td>
                            <td class="px-4 py-2 text-sm text-right font-medium">{{ isset($row['amount']) ? format_currency($row['amount']) : '-' }}</td>
                            <td class="px-4 py-2 text-sm">{{ $row['user'] ?? 'Unknown' }}</td>
                            <td class="px-4 py-2 text-sm">{{ $row['batch'] ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No results found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search
    const searchInput = document.getElementById('searchResults');
    searchInput?.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.result-row').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });

    // Sort
    const sortSelect = document.getElementById('sortResults');
    sortSelect?.addEventListener('change', function() {
        const tbody = document.querySelector('#resultsTable tbody');
        const rows = Array.from(tbody.querySelectorAll('.result-row'));
        const sortBy = this.value;

        rows.sort((a, b) => {
            switch(sortBy) {
                case 'date_desc':
                    return new Date(b.dataset.date) - new Date(a.dataset.date);
                case 'date_asc':
                    return new Date(a.dataset.date) - new Date(b.dataset.date);
                case 'amount_desc':
                    return parseFloat(b.dataset.amount) - parseFloat(a.dataset.amount);
                case 'amount_asc':
                    return parseFloat(a.dataset.amount) - parseFloat(b.dataset.amount);
                default: return 0;
            }
        });

        rows.forEach(row => tbody.appendChild(row));
    });
});

// Export CSV
function exportResults() {
    const rows = document.querySelectorAll('.result-row:not([style*="display: none"])');
    if (!rows.length) {
        alert('No results to export.');
        return;
    }

    let csv = 'Date,Type,Description,Amount,User,Batch\n';
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const date = cells[0].textContent.trim();
        const type = cells[1].textContent.trim();
        const desc = cells[2].textContent.trim().replace(/,/g, ';');
        const amount = cells[3].textContent.trim();
        const user = cells[4].textContent.trim();
        const batch = cells[5].textContent.trim();
        csv += `"${date}","${type}","${desc}","${amount}","${user}","${batch}"\n`;
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'query_results.csv';
    link.click();
}
</script>
@endpush