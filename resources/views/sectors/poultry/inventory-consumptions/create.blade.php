@extends('layouts.app')

@section('title', 'Record Inventory Consumption - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Record Inventory Consumption</h1>
        <p class="text-sm text-gray-600">Log stock usage for feed, medicines, and other items.</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('poultry.forms.inventory-consumption.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="inventory_item_id" class="block text-sm font-medium text-gray-700">Item <span class="text-red-500">*</span></label>
                <select name="inventory_item_id" id="inventory_item_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                    <option value="">-- Select Item --</option>
                    @foreach($inventoryItems ?? [] as $item)
                        <option value="{{ $item->id }}" {{ old('inventory_item_id', $selectedItem?->id) == $item->id ? 'selected' : '' }}>
                            {{ $item->name }} ({{ $item->quantity_in_stock }} {{ $item->unit }} available)
                        </option>
                    @endforeach
                </select>
                @error('inventory_item_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="poultry_batch_id" class="block text-sm font-medium text-gray-700">Batch (Optional)</label>
                <select name="poultry_batch_id" id="poultry_batch_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="">-- General Usage --</option>
                    @foreach($batches ?? [] as $batch)
                        <option value="{{ $batch->id }}" {{ old('poultry_batch_id') == $batch->id ? 'selected' : '' }}>
                            {{ $batch->batch_id }} - {{ $batch->name }}
                        </option>
                    @endforeach
                </select>
                @error('poultry_batch_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="date" class="block text-sm font-medium text-gray-700">Date <span class="text-red-500">*</span></label>
                <input type="date" name="date" id="date" value="{{ old('date', now()->toDateString()) }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                @error('date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="quantity_used" class="block text-sm font-medium text-gray-700">Quantity Used <span class="text-red-500">*</span></label>
                <input type="number" name="quantity_used" id="quantity_used" value="{{ old('quantity_used') }}" step="0.001" min="0.001" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                @error('quantity_used') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Optional notes...">{{ old('notes') }}</textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('poultry.inventory.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">Save Usage</button>
            </div>
        </form>
    </div>
</div>
@endsection
