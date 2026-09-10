@extends('layouts.app')

@section('title', 'Edit Feed Record - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Feed Record</h1>
        <p class="text-sm text-gray-600">Update feed consumption for {{ $feedRecord->batch->batch_id ?? 'this batch' }}</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('poultry.feed-records.update', $feedRecord) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="inventory_item_id" class="block text-sm font-medium text-gray-700">Feed Type <span class="text-red-500">*</span></label>
                <select name="inventory_item_id" id="inventory_item_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                    <option value="">-- Select Feed --</option>
                    @foreach($inventoryItems ?? [] as $item)
                        <option value="{{ $item->id }}" {{ old('inventory_item_id', $feedRecord->inventory_item_id) == $item->id ? 'selected' : '' }}>
                            {{ $item->name }} ({{ $item->quantity_in_stock }} {{ $item->unit }} available)
                        </option>
                    @endforeach
                </select>
                @error('inventory_item_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="date" class="block text-sm font-medium text-gray-700">Date <span class="text-red-500">*</span></label>
                <input type="date" name="date" id="date" value="{{ old('date', $feedRecord->date->format('Y-m-d')) }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                @error('date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="feed_used" class="block text-sm font-medium text-gray-700">Feed Used (kg) <span class="text-red-500">*</span></label>
                <input type="number" name="feed_used" id="feed_used" value="{{ old('feed_used', $feedRecord->feed_used) }}" step="0.001" min="0.001" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                @error('feed_used') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('poultry.batches.show', $feedRecord->batch) }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">Update Record</button>
            </div>
        </form>
    </div>
</div>
@endsection
