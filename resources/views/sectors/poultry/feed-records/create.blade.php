@extends('layouts.app')

@section('title', 'Record Feed - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Record Feed</h1>
        <p class="text-sm text-gray-600">Record daily feed consumption for a batch</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('poultry.forms.feed-record.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="poultry_batch_id" class="block text-sm font-medium text-gray-700">Batch <span class="text-red-500">*</span></label>
                <select name="poultry_batch_id" id="poultry_batch_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                    <option value="">-- Select Batch --</option>
                    @foreach($batches ?? [] as $item)
                        <option value="{{ $item->id }}" {{ old('poultry_batch_id', $batch?->id) == $item->id ? 'selected' : '' }}>
                            {{ $item->batch_id }} - {{ $item->name }} ({{ $item->remaining_flock }} birds)
                        </option>
                    @endforeach
                </select>
                @error('poultry_batch_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="inventory_item_id" class="block text-sm font-medium text-gray-700">Feed Type <span class="text-red-500">*</span></label>
                <select name="inventory_item_id" id="inventory_item_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                    <option value="">-- Select Feed --</option>
                    @foreach($inventoryItems ?? [] as $item)
                        <option value="{{ $item->id }}" {{ old('inventory_item_id') == $item->id ? 'selected' : '' }}>
                            {{ $item->name }} ({{ $item->quantity_in_stock }} {{ $item->unit }} available)
                        </option>
                    @endforeach
                </select>
                @error('inventory_item_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="date" class="block text-sm font-medium text-gray-700">Date <span class="text-red-500">*</span></label>
                <input type="date" name="date" id="date" value="{{ old('date', now()->toDateString()) }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                @error('date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="feed_used" class="block text-sm font-medium text-gray-700">Feed Used (kg) <span class="text-red-500">*</span></label>
                <input type="number" name="feed_used" id="feed_used" value="{{ old('feed_used') }}" step="0.001" min="0.001" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="0.000" required>
                @error('feed_used') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ $batch ? route('poultry.batches.show', $batch) : route('poultry.batches.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">Save Record</button>
            </div>
        </form>
    </div>
</div>
@endsection
