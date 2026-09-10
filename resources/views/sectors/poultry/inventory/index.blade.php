@extends('layouts.app')

@section('title', 'Inventory - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Inventory Management</h1>
        <p class="text-sm text-gray-600">Manage farm inventory including feed, vaccines, medications, and supplies</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-filter mr-2"></i> Filter <i class="fas fa-chevron-down ml-2"></i>
            </button>
            <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10" style="display: none;">
                <div class="py-1">
                    <a href="{{ route('poultry.inventory.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">All Items</a>
                    <a href="{{ route('poultry.inventory.index', ['category' => 'feed']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Feed</a>
                    <a href="{{ route('poultry.inventory.index', ['category' => 'medicine']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Medicine</a>
                    <a href="{{ route('poultry.inventory.index', ['category' => 'vaccine']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Vaccine</a>
                    <div class="border-t border-gray-100"></div>
                    <a href="{{ route('poultry.inventory.index', ['status' => 'low']) }}" class="block px-4 py-2 text-sm text-red-700 hover:bg-gray-100">Low Stock</a>
                    <a href="{{ route('poultry.inventory.index', ['status' => 'out']) }}" class="block px-4 py-2 text-sm text-red-700 hover:bg-gray-100">Out of Stock</a>
                </div>
            </div>
        </div>
        <a href="{{ route('poultry.inventory.waste') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-trash-alt mr-2"></i> Waste Log
        </a>
        <a href="{{ route('poultry.inventory.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            <i class="fas fa-plus mr-2"></i> New Item
        </a>

        <!-- Toggle Killed Items Filter -->
        @if(request()->boolean('show_killed'))
            <a href="{{ route('poultry.inventory.index', array_merge(request()->except('show_killed'), ['show_killed' => 0])) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-eye-slash mr-2"></i> Hide Killed
            </a>
        @else
            <a href="{{ route('poultry.inventory.index', array_merge(request()->except('show_killed'), ['show_killed' => 1])) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-eye mr-2"></i> Show Killed
            </a>
        @endif
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-{{ auth()->user()->role === 'admin' ? '3' : '2' }} gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div><p class="text-sm font-medium text-gray-600">Total Items</p><p class="text-3xl font-bold text-gray-900">{{ $items->count() }}</p></div>
                <div class="w-12 h-12 rounded-lg bg-primary-500 flex items-center justify-center"><i class="fas fa-boxes text-white text-xl"></i></div>
            </div>
        </div>
        @if(auth()->user()->role === 'admin')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div><p class="text-sm font-medium text-gray-600">Total Value</p><p class="text-3xl font-bold text-gray-900">{{ format_currency($totalValue ?? 0) }}</p></div>
            </div>
        </div>
        @endif
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div><p class="text-sm font-medium text-gray-600">Alerts</p><p class="text-3xl font-bold text-gray-900">{{ $items->filter(fn($i) => $i->isLowStock())->count() }}</p></div>
                <div class="w-12 h-12 rounded-lg bg-yellow-500 flex items-center justify-center"><i class="fas fa-exclamation-triangle text-white text-xl"></i></div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px] relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400"></i></div>
                <input type="text" name="search" value="{{ request('search') }}" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md sm:text-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Search inventory...">
            </div>
            <div>
                <select name="category" class="block w-full pl-3 pr-10 py-2 border border-gray-300 rounded-md sm:text-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="">All Categories</option>
                    @foreach(['feed','vaccine','medicine','consumables','packaging','other'] as $cat)
                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="status" class="block w-full pl-3 pr-10 py-2 border border-gray-300 rounded-md sm:text-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="">All Status</option>
                    <option value="low" {{ request('status') == 'low' ? 'selected' : '' }}>Low Stock</option>
                    <option value="out" {{ request('status') == 'out' ? 'selected' : '' }}>Out of Stock</option>
                </select>
            </div>
            <!-- Preserve show_killed parameter -->
            <input type="hidden" name="show_killed" value="{{ request('show_killed', 0) }}">
            <button type="submit" class="px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700">Filter</button>
            @if(request()->hasAny(['search','category','status']))
            <a href="{{ route('poultry.inventory.index', ['show_killed' => request('show_killed', 0)]) }}" class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Clear</a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Cost</th>
                        @if(auth()->user()->role === 'admin')
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Value</th>
                        @endif
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-lg {{ $item->category == 'feed' ? 'bg-yellow-100' : ($item->category == 'medicine' ? 'bg-red-100' : 'bg-gray-100') }} flex items-center justify-center">
                                    <i class="fas {{ $item->category == 'feed' ? 'fa-seedling text-yellow-600' : ($item->category == 'medicine' ? 'fa-pills text-red-600' : 'fa-box text-gray-600') }}"></i>
                                </div>
                                <div class="ml-4">
                                    <a href="{{ route('poultry.inventory.show', $item) }}" class="text-sm font-medium text-gray-900 hover:text-primary-700">{{ $item->name }}</a>
                                    <div class="text-xs text-gray-500">{{ ucfirst($item->category) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap"><span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">{{ ucfirst($item->category) }}</span></td>
                        <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-medium">{{ $item->quantity_in_stock }} {{ $item->unit }}</div></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ format_currency($item->cost_per_unit) }}</td>
                        @if(auth()->user()->role === 'admin')
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ format_currency($item->quantity_in_stock * $item->cost_per_unit) }}</td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if(!$item->is_active)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">Killed</span>
                            @elseif($item->isOutOfStock())
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">Out of Stock</span>
                            @elseif($item->isLowStock())
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Low Stock</span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">In Stock</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('poultry.inventory.show', $item) }}" class="text-primary-600 hover:text-primary-900 mr-2"><i class="fas fa-eye"></i></a>
                            @if($item->is_active)
                                <a href="{{ route('poultry.forms.inventory-consumption.create', ['inventory_item' => $item->id]) }}" class="text-green-600 hover:text-green-900 mr-2"><i class="fas fa-minus-circle"></i></a>
                                <form method="POST" action="{{ route('poultry.inventory.kill', $item) }}" class="inline" onsubmit="return confirm('Kill this item?')">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:text-red-900 bg-transparent border-0 p-0 cursor-pointer"><i class="fas fa-skull-crossbones"></i></button>
                                </form>
                            @else
                                <span class="text-gray-400" title="Item is killed"><i class="fas fa-ban"></i></span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ auth()->user()->role === 'admin' ? '7' : '6' }}" class="px-6 py-12 text-center text-gray-500">No inventory items found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">{{ $items->links() }}</div>
        @endif
    </div>
</div>
@endsection