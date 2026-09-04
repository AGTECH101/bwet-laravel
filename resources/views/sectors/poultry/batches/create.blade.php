@extends('layouts.app')

@section('title', 'Create Batch - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Create New Batch</h1>
        <p class="text-sm text-gray-600">Set up a new poultry production batch</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <a href="{{ route('poultry.batches.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-times mr-2"></i> Cancel
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('poultry.batches.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="batch_id" class="block text-sm font-medium text-gray-700">Batch ID <span class="text-red-500">*</span></label>
                <input type="text" name="batch_id" id="batch_id" value="{{ old('batch_id') }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="e.g., BATCH-2024-001">
                @error('batch_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-500">Leave blank to auto-generate</p>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Batch Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="e.g., Summer Batch 2024">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="hatchery" class="block text-sm font-medium text-gray-700">Hatchery (Breed)</label>
                <input type="text" name="hatchery" id="hatchery" value="{{ old('hatchery') }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="e.g., Broiler...">
                @error('hatchery') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date <span class="text-red-500">*</span></label>
                <input type="date" name="start_date" id="start_date" value="{{ old('start_date', now()->toDateString()) }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                @error('start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="starting_flock" class="block text-sm font-medium text-gray-700">Starting Flock Size <span class="text-red-500">*</span></label>
                <input type="number" name="starting_flock" id="starting_flock" value="{{ old('starting_flock') }}" min="0" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Number of birds">
                @error('starting_flock') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="initial_chicken_cost" class="block text-sm font-medium text-gray-700">Total Purchasing Cost <span class="text-red-500">*</span></label>
                <input type="number" name="initial_chicken_cost" id="initial_chicken_cost" value="{{ old('initial_chicken_cost', 0) }}" step="0.01" min="0" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="0.00">
                @error('initial_chicken_cost') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phase" class="block text-sm font-medium text-gray-700">Production Phase <span class="text-red-500">*</span></label>
                <select name="phase" id="phase" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="brooding" {{ old('phase') == 'brooding' ? 'selected' : '' }}>Brooding</option>
                    <option value="batch" {{ old('phase') == 'batch' ? 'selected' : '' }}>Batch</option>
                </select>
                @error('phase') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div id="penSelection" style="{{ old('phase') == 'batch' ? 'display:block' : 'display:none' }}">
                <label class="block text-sm font-medium text-gray-700">Pen Assignment</label>
                <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-sm text-gray-600">Pen will be auto-assigned from available pens.</p>
                    @if(isset($availablePens) && count($availablePens) > 0)
                    <p class="text-xs text-green-600 mt-1">{{ count($availablePens) }} pen(s) available</p>
                    @else
                    <p class="text-xs text-red-600 mt-1">No available pens! Contact admin.</p>
                    @endif
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('poultry.batches.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">Create Batch</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const phaseSelect = document.getElementById('phase');
    const penSelection = document.getElementById('penSelection');

    phaseSelect.addEventListener('change', function() {
        penSelection.style.display = this.value === 'batch' ? 'block' : 'none';
    });
});
</script>
@endpush
@endsection