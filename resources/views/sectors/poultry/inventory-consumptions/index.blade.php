@extends('layouts.app')

@section('title', 'Inventory Consumption - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Inventory Consumption</h1>
        <p class="text-sm text-gray-600">Track how stock items are used in production batches.</p>
    </div>
</div>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900">Consumption History</h3>
        <a href="{{ route('poultry.forms.inventory-consumption.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            <i class="fas fa-plus mr-2"></i> Add Usage
        </a>
    </div>

    <div class="p-6">
        @if(($consumptions ?? collect())->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($consumptions as $consumption)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $consumption->date->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $consumption->inventoryItem?->name ?? 'Unknown item' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $consumption->batch?->batch_id ?? 'General' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $consumption->quantity_used }} {{ $consumption->inventoryItem?->unit ?? '' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ format_currency($consumption->total_cost ?? 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-gray-500">No inventory usage recorded yet.</p>
            </div>
        @endif
    </div>
</div>
@endsection
