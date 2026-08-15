@extends('layouts.app')

@section('title', 'Edit Weight Record - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Weight Record</h1>
        <p class="text-sm text-gray-600">Update weight sample for {{ $weightRecord->batch->batch_id ?? 'this batch' }}</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('poultry.weight-records.update', $weightRecord) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="date" class="block text-sm font-medium text-gray-700">Date <span class="text-red-500">*</span></label>
                <input type="date" name="date" id="date" value="{{ old('date', $weightRecord->date->format('Y-m-d')) }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                @error('date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="individual_weights" class="block text-sm font-medium text-gray-700">Sample Weights (kg) <span class="text-red-500">*</span></label>
                <textarea name="individual_weights" id="individual_weights" rows="6" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Example: 1.25
1.32
1.18">{{ old('individual_weights', implode("\n", $weightRecord->individual_weights ?? [])) }}</textarea>
                @error('individual_weights') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Optional notes...">{{ old('notes', $weightRecord->notes) }}</textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('poultry.batches.show', $weightRecord->batch) }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">Update Record</button>
            </div>
        </form>
    </div>
</div>
@endsection
