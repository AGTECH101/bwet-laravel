@extends('layouts.app')

@section('title', $inventory->name . ' - Inventory')

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $inventory->name ?? 'Unnamed Item' }}</h1>
        <p class="text-sm text-gray-600">Inventory Item Details</p>
        @if(!$inventory->is_active)
            <span class="mt-1 inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">Killed (Deactivated)</span>
        @endif
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
        <a href="{{ route('poultry.inventory.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
        @if($inventory->is_active)
            <a href="{{ route('poultry.forms.inventory-consumption.create', ['inventory_item' => $inventory->id]) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700">
                <i class="fas fa-minus-circle mr-2"></i> Use Item
            </a>
            @can('update', $inventory)
                <a href="{{ route('poultry.inventory.edit', $inventory) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                    <i class="fas fa-edit mr-2"></i> Edit
                </a>
            @endcan
        @endif
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Summary -->
    <div class="md:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
            <div class="mb-3 text-4xl">
                @if($inventory->category == 'feed')🍽️
                @elseif($inventory->category == 'medicine')💊
                @elseif($inventory->category == 'vaccine')💉
                @else📦
                @endif
            </div>
            <h3 class="text-lg font-semibold text-gray-900">{{ $inventory->name ?? 'Unnamed' }}</h3>
            <div class="flex items-center justify-center mt-2 space-x-2">
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ ucfirst($inventory->category ?? 'N/A') }}</span>
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">{{ ucfirst($inventory->unit ?? 'N/A') }}</span>
            </div>

            <!-- Stock Level -->
            <div class="mt-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Stock Level</span>
                    <span class="font-bold">
                        {{ $inventory->minimum_quantity > 0 ? min(($inventory->quantity_in_stock / $inventory->minimum_quantity) * 100, 100) : 0 }}%
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                    <div class="h-2 rounded-full {{ $inventory->isOutOfStock() ? 'bg-red-500' : ($inventory->isLowStock() ? 'bg-yellow-500' : 'bg-green-500') }}"
                         style="width: {{ $inventory->minimum_quantity > 0 ? min(($inventory->quantity_in_stock / $inventory->minimum_quantity) * 100, 100) : 0 }}%">
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    Min: {{ $inventory->minimum_quantity ?? 0 }} {{ $inventory->unit ?? '' }} | Current: {{ $inventory->quantity_in_stock ?? 0 }} {{ $inventory->unit ?? '' }}
                </p>
            </div>

            <!-- Financial -->
            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Cost per Unit</span>
                    <span class="font-bold">{{ $inventory->cost_per_unit ? number_format($inventory->cost_per_unit, 2) : 'N/A' }}</span>
                </div>
                <div class="flex justify-between text-sm mt-1">
                    <span class="text-gray-600">Total Value</span>
                    <span class="font-bold">{{ $inventory->quantity_in_stock && $inventory->cost_per_unit ? number_format($inventory->quantity_in_stock * $inventory->cost_per_unit, 2) : 'N/A' }}</span>
                </div>
                <div class="flex justify-between text-sm mt-1">
                    <span class="text-gray-600">Quantity Used</span>
                    <span class="font-bold">{{ $inventory->quantity_used ?? 0 }} {{ $inventory->unit ?? '' }}</span>
                </div>
            </div>

            <!-- Status -->
            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Status</span>
                    <span>
                        @if(!$inventory->is_active)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">Killed</span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                        @endif
                    </span>
                </div>
                @if(!$inventory->is_active && $inventory->killed_at)
                <div class="text-xs text-gray-500 mt-1">
                    Killed on {{ $inventory->killed_at ? $inventory->killed_at->format('M d, Y') : 'N/A' }}
                    @if($inventory->killedBy) by {{ $inventory->killedBy->name }} @endif
                    @if($inventory->killed_reason)
                        <br><span class="text-gray-600">Reason: {{ $inventory->killed_reason }}</span>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Details & History -->
    <div class="md:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Item Details</h3>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><dt class="text-sm font-medium text-gray-500">Name</dt><dd class="text-sm text-gray-900">{{ $inventory->name ?? 'N/A' }}</dd></div>
                <div><dt class="text-sm font-medium text-gray-500">Category</dt><dd class="text-sm text-gray-900">{{ ucfirst($inventory->category ?? 'N/A') }}</dd></div>
                <div><dt class="text-sm font-medium text-gray-500">Unit</dt><dd class="text-sm text-gray-900">{{ ucfirst($inventory->unit ?? 'N/A') }}</dd></div>
                <div><dt class="text-sm font-medium text-gray-500">Vendor</dt><dd class="text-sm text-gray-900">{{ $inventory->vendor ?? 'Not specified' }}</dd></div>
                <div><dt class="text-sm font-medium text-gray-500">Created</dt><dd class="text-sm text-gray-900">{{ $inventory->created_at ? $inventory->created_at->format('M d, Y') : 'N/A' }}</dd></div>
                <div><dt class="text-sm font-medium text-gray-500">Created By</dt><dd class="text-sm text-gray-900">{{ $inventory->createdBy?->name ?? 'System' }}</dd></div>
            </dl>
        </div>

        <!-- Consumption History -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Consumption History</h3>
                <span class="text-xs text-gray-500">Last 20 records</span>
            </div>
            @if(isset($consumptionHistory) && count($consumptionHistory) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Batch</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Quantity</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Total Cost</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($consumptionHistory as $consumption)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm">{{ $consumption->date->format('M d, Y') }}</td>
                            <td class="px-4 py-2 text-sm">{{ $consumption->batch?->batch_id ?? 'General' }}</td>
                            <td class="px-4 py-2 text-sm">{{ $consumption->quantity_used }} {{ $inventory->unit ?? '' }}</td>
                            <td class="px-4 py-2 text-sm font-medium">{{ number_format($consumption->total_cost, 2) }}</td>
                            <td class="px-4 py-2 text-sm">{{ $consumption->recordedBy?->name ?? 'System' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-sm text-gray-500 text-center py-4">No consumption history found</p>
            @endif
        </div>

        <!-- Actions -->
        <div class="mt-6 flex gap-3">
            @if($inventory->is_active)
                <form method="POST" action="{{ route('poultry.inventory.kill', $inventory) }}" onsubmit="return confirm('Kill this inventory item? This cannot be undone.')">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                        <i class="fas fa-skull-crossbones mr-2"></i> Kill Item
                    </button>
                </form>
            @endif
            @can('recalculate-costs', $inventory)
                <form method="POST" action="{{ route('poultry.inventory.recalculate', $inventory) }}" onsubmit="return confirm('Recalculate all historical costs? This will update all consumption records.')">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-calculator mr-2"></i> Recalculate Costs
                    </button>
                </form>
            @endcan
        </div>
    </div>
</div>
@endsection