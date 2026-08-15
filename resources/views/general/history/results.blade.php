@extends('layouts.app')

@section('title', 'History Results - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Query Results</h1>
        <p class="text-sm text-gray-600">Results returned from the history explorer.</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <a href="{{ route('history.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back to History
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900">Returned Records</h3>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
            {{ $results->count() ?? 0 }} records
        </span>
    </div>

    <div class="p-6">
        @if(($results ?? collect())->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($results as $row)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row['date'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ ucfirst($row['type'] ?? 'record') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $row['description'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ isset($row['amount']) && $row['amount'] !== null ? format_currency($row['amount']) : '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $row['user'] ?? 'Unknown' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $row['batch'] ?? '-' }}</td>
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
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Results Found</h3>
                <p class="text-gray-500 max-w-md mx-auto">Try a different query or adjust your date filters.</p>
            </div>
        @endif
    </div>
</div>
@endsection
