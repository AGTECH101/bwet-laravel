@extends('layouts.app')

@section('title', 'Edit ' . $batch->batch_id . ' - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Batch: {{ $batch->batch_id }}</h1>
        <p class="text-sm text-gray-600">{{ $batch->name }}</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
        <a href="{{ route('poultry.batches.show', $batch) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('poultry.batches.update', $batch) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Batch Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $batch->name) }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="hatchery" class="block text-sm font-medium text-gray-700">Hatchery (Breed)</label>
                <input type="text" name="hatchery" id="hatchery" value="{{ old('hatchery', $batch->hatchery) }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                @error('hatchery') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date <span class="text-red-500">*</span></label>
                <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $batch->start_date->toDateString()) }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                @error('start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phase" class="block text-sm font-medium text-gray-700">Production Phase <span class="text-red-500">*</span></label>
                <select name="phase" id="phase" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="brooding" {{ old('phase', $batch->phase) == 'brooding' ? 'selected' : '' }}>Brooding</option>
                    <option value="batch" {{ old('phase', $batch->phase) == 'batch' ? 'selected' : '' }}>Batch</option>
                </select>
                @error('phase') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div id="penSelection" style="{{ old('phase', $batch->phase) == 'batch' ? 'display:block' : 'display:none' }}">
                <label class="block text-sm font-medium text-gray-700">Pen Assignment</label>
                <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    @if($batch->pen)
                    <p class="text-sm text-gray-900">Current: {{ $batch->pen->name }}</p>
                    @else
                    <p class="text-sm text-gray-600">No pen assigned. Will be auto-assigned if available.</p>
                    @endif
                    <p class="text-xs text-gray-500 mt-1">Note: Pen assignment is automatic based on availability.</p>
                </div>
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="status" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="active" {{ old('status', $batch->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="closed" {{ old('status', $batch->status) == 'closed' ? 'selected' : '' }}>Closed</option>
                    <option value="completed" {{ old('status', $batch->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
                @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('poultry.batches.show', $batch) }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">Update Batch</button>
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