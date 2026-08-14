@extends('layouts.app')

@section('title', 'Observations - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Observation Reports</h1>
        <p class="text-sm text-gray-600">Monitor and manage farm observation reports</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <a href="{{ route('observations.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            <i class="fas fa-plus mr-2"></i> New Report
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div><p class="text-sm font-medium text-gray-600">Total</p><p class="text-3xl font-bold text-gray-900">{{ $stats['total'] ?? 0 }}</p></div>
                <div class="w-12 h-12 rounded-lg bg-primary-500 flex items-center justify-center"><i class="fas fa-clipboard-list text-white text-xl"></i></div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div><p class="text-sm font-medium text-gray-600">Pending</p><p class="text-3xl font-bold text-yellow-600">{{ $stats['pending'] ?? 0 }}</p></div>
                <div class="w-12 h-12 rounded-lg bg-yellow-500 flex items-center justify-center"><i class="fas fa-clock text-white text-xl"></i></div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div><p class="text-sm font-medium text-gray-600">Resolved</p><p class="text-3xl font-bold text-green-600">{{ $stats['resolved'] ?? 0 }}</p></div>
                <div class="w-12 h-12 rounded-lg bg-green-500 flex items-center justify-center"><i class="fas fa-check-circle text-white text-xl"></i></div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div><p class="text-sm font-medium text-gray-600">Critical</p><p class="text-3xl font-bold text-red-600">{{ collect($stats['by_priority'] ?? [])->firstWhere('priority', 'critical')['count'] ?? 0 }}</p></div>
                <div class="w-12 h-12 rounded-lg bg-red-500 flex items-center justify-center"><i class="fas fa-exclamation-triangle text-white text-xl"></i></div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">All</option>
                    @foreach(['pending','reviewed','resolved','closed'] as $s)
                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">All</option>
                    @foreach(['low','medium','high','critical'] as $p)
                    <option value="{{ $p }}" {{ request('priority') == $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700">Apply</button>
                @if(request()->hasAny(['status','priority']))
                <a href="{{ route('observations.index') }}" class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reported By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($observations as $obs)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('observations.show', $obs) }}" class="text-sm font-medium text-gray-900 hover:text-primary-700">
                                {{ Str::limit($obs->title, 50) }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $obs->category?->name ?? $obs->other_category ?? 'Uncategorized' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{!! observation_status_badge($obs->status) !!}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{!! priority_badge($obs->priority) !!}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $obs->reportedBy?->name ?? 'Unknown' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $obs->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('observations.show', $obs) }}" class="text-primary-600 hover:text-primary-900 mr-2"><i class="fas fa-eye"></i></a>
                            @can('review', $obs)
                            @if($obs->status === 'pending')
                            <a href="{{ route('observations.review', $obs) }}" class="text-green-600 hover:text-green-900"><i class="fas fa-check"></i></a>
                            @endif
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">No observations found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($observations->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">{{ $observations->links() }}</div>
        @endif
    </div>
</div>
@endsection