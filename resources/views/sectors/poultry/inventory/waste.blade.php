@extends('layouts.app')

@section('title', 'Inventory Waste - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Inventory Waste Log</h1>
        <p class="text-sm text-gray-600">Spoilage, breakage, expiry, and other losses that were not assigned to any batch cost.</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
        <a href="{{ route('poultry.forms.inventory-waste.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-amber-600 hover:bg-amber-700">
            <i class="fas fa-plus mr-2"></i> Record Waste
        </a>
        <a href="{{ route('poultry.inventory.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back to Inventory
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recorded By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Note</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($wasteRecords as $record)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->date->format('M d, Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->inventoryItem?->name ?? 'Unknown item' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->batch?->batch_id ?? 'No batch' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->quantity_used }} {{ $record->inventoryItem?->unit ?? '' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->reason }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->recordedBy?->name ?? 'System' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 max-w-md">{{ $record->notes }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-box-open text-2xl mb-2"></i>
                            <p>No inventory waste records found.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($wasteRecords->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $wasteRecords->links() }}
        </div>
    @endif
</div>
@endsection
