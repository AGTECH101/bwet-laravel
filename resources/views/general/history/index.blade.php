@extends('layouts.app')

@section('title', 'History Query - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">History Query Explorer</h1>
        <p class="text-sm text-gray-600">Search and analyze historical farm data across all record types</p>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Query Builder -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden sticky top-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-primary-50 to-primary-100">
                <h3 class="text-lg font-semibold text-gray-900">Query Builder</h3>
            </div>
            <div class="p-6">
                <form method="POST" action="{{ route('history.execute') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Query Name (Optional)</label>
                        <input type="text" name="name" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="e.g., Feed Costs January">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Query Type *</label>
                        <select name="query_type" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                            <option value="">Select type</option>
                            <option value="expenses">Expenses</option>
                            <option value="feed">Feed Records</option>
                            <option value="weight">Weight Records</option>
                            <option value="flock">Flock Records</option>
                            <option value="observations">Observations</option>
                            <option value="inventory">Inventory</option>
                            <option value="all">All Records</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500">From</label>
                            <input type="date" name="date_from" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">To</label>
                            <input type="date" name="date_to" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">User</label>
                        <select name="user_filter" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                            <option value="">All Users</option>
                            @foreach($users ?? [] as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Batch</label>
                        <select name="batch_filter" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                            <option value="">All Batches</option>
                            @foreach($batches ?? [] as $b)
                            <option value="{{ $b->id }}">{{ $b->batch_id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Category</label>
                        <input type="text" name="category_filter" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="e.g., medication">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Min Amount</label>
                            <input type="number" step="0.01" name="min_amount" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Max Amount</label>
                            <input type="number" step="0.01" name="max_amount" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                        <i class="fas fa-play mr-2"></i> Execute Query
                    </button>
                </form>
            </div>
        </div>

        <!-- Recent Queries -->
        <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900"><i class="fas fa-history mr-2"></i>Recent Queries</h3>
            </div>
            <div class="p-4 max-h-96 overflow-y-auto">
                @forelse($recentQueries ?? [] as $query)
                <div class="mb-3 p-3 rounded-lg border border-gray-200 hover:border-primary-300 hover:bg-primary-50 cursor-pointer transition-colors" onclick="window.location='{{ route('history.show', $query) }}'">
                    <div class="flex justify-between items-start">
                        <span class="text-sm font-medium text-gray-900">{{ $query->name ?: 'Unnamed Query' }}</span>
                        <span class="text-xs text-gray-500">{{ $query->updated_at->diffForHumans() }}</span>
                    </div>
                    <div class="flex items-center mt-1 space-x-2">
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ ucfirst($query->query_type) }}</span>
                        <span class="text-xs text-gray-600"><i class="fas fa-chart-bar mr-1"></i> {{ $query->result_count }} results</span>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-500 text-center py-4">No saved queries</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Results -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900"><i class="fas fa-table mr-2"></i>Query Results</h3>
                @if(isset($results) && count($results) > 0)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800">{{ count($results) }} records</span>
                @endif
            </div>
            <div class="p-6">
                @if(isset($results) && count($results) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
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
                            @foreach($results as $row)
                            <tr class="hover:bg-gray-50">
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-12">
                    <div class="w-20 h-20 mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <i class="fas fa-search text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Results Yet</h3>
                    <p class="text-gray-500 max-w-md mx-auto">Build a query using the form and click "Execute Query" to see results here.</p>
                    <button onclick="document.querySelector('form').scrollIntoView({behavior:'smooth'})" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                        <i class="fas fa-arrow-up mr-2"></i> Build Query
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection