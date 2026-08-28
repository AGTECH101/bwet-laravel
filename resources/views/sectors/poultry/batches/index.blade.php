@extends('layouts.app')

@section('title', 'Batches - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Batch Management</h1>
        <p class="text-sm text-gray-600">Monitor and manage all poultry batches</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-filter mr-2"></i> Filter <i class="fas fa-chevron-down ml-2"></i>
            </button>
            <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10" style="display: none;">
                <div class="py-1">
                    <a href="{{ route('poultry.batches.index', ['show_closed' => request('show_closed', 0)]) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">All</a>
                    <a href="{{ route('poultry.batches.index', ['status' => 'active', 'show_closed' => request('show_closed', 0)]) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Active</a>
                    <a href="{{ route('poultry.batches.index', ['status' => 'closed', 'show_closed' => request('show_closed', 0)]) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Closed</a>
                    <a href="{{ route('poultry.batches.index', ['status' => 'completed', 'show_closed' => request('show_closed', 0)]) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Completed</a>
                </div>
            </div>
        </div>

        <!-- Toggle Show Closed -->
        @if(request()->boolean('show_closed'))
            <a href="{{ route('poultry.batches.index', array_merge(request()->except('show_closed'), ['show_closed' => 0])) }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-eye-slash mr-2"></i> Hide Closed
            </a>
        @else
            <a href="{{ route('poultry.batches.index', array_merge(request()->except('show_closed'), ['show_closed' => 1])) }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-eye mr-2"></i> Show Closed
            </a>
        @endif

        <a href="{{ route('poultry.batches.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            <i class="fas fa-plus mr-2"></i> New Batch
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center"><i class="fas fa-play-circle text-green-600"></i></div>
                <div class="ml-4"><p class="text-sm text-gray-600">Active</p><p class="text-2xl font-bold text-gray-900">{{ \App\Models\Poultry\Batch::where('status','active')->count() }}</p></div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center"><i class="fas fa-kiwi-bird text-blue-600"></i></div>
                <div class="ml-4"><p class="text-sm text-gray-600">Total Birds</p><p class="text-2xl font-bold text-gray-900">{{ \App\Models\Poultry\Batch::where('status','active')->sum('remaining_flock') }}</p></div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center"><i class="fas fa-chart-line text-purple-600"></i></div>
                <div class="ml-4"><p class="text-sm text-gray-600">Avg. FCR</p><p class="text-2xl font-bold text-gray-900">{{ number_format(\App\Models\Poultry\Batch::where('status','active')->where('current_cfcr','>',0)->avg('current_cfcr'), 3) }}</p></div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <form method="GET" class="flex items-center space-x-2">
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400"></i></div>
                <input type="text" name="search" value="{{ request('search') }}" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md sm:text-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Search batches by ID or name...">
                <input type="hidden" name="show_closed" value="{{ request('show_closed', 0) }}">
            </div>
            <button type="submit" class="px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700">Search</button>
            @if(request()->has('search') || request()->has('status'))
            <a href="{{ route('poultry.batches.index', ['show_closed' => request('show_closed', 0)]) }}" class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Clear</a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Performance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($batches as $batch)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center"><i class="fas fa-layer-group text-primary-600"></i></div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium"><a href="{{ route('poultry.batches.show', $batch) }}" class="hover:text-primary-700">{{ $batch->batch_id }}</a></div>
                                    <div class="text-sm text-gray-500">{{ Str::limit($batch->name, 30) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{!! batch_status_badge($batch->status) !!}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-24 bg-gray-200 rounded-full h-2 mr-3">
                                    <div class="bg-primary-600 h-2 rounded-full" style="width: {{ ($batch->remaining_flock / max($batch->starting_flock, 1)) * 100 }}%"></div>
                                </div>
                                <div class="text-sm text-gray-900">{{ $batch->remaining_flock }}/{{ $batch->starting_flock }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $batch->age_days }} days</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-4">
                                <div><div class="text-sm">{{ format_weight($batch->getCurrentAverageWeight()) }}</div><div class="text-xs text-gray-500">Weight</div></div>
                                <div><div class="text-sm font-medium {{ $batch->current_cfcr < 1.8 ? 'text-green-600' : ($batch->current_cfcr < 2.0 ? 'text-yellow-600' : 'text-red-600') }}">{{ format_fcr($batch->current_cfcr) }}</div><div class="text-xs text-gray-500">FCR</div></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('poultry.batches.show', $batch) }}" class="text-primary-600 hover:text-primary-900" title="View"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('poultry.forms.hub') }}?batch={{ $batch->batch_id }}" class="text-green-600 hover:text-green-900" title="Add Record"><i class="fas fa-plus"></i></a>
                                <a href="{{ route('poultry.batches.export', $batch) }}" class="text-purple-600 hover:text-purple-900" title="Export"><i class="fas fa-file-export"></i></a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">No batches found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($batches->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">{{ $batches->links() }}</div>
        @endif
    </div>
</div>
@endsection