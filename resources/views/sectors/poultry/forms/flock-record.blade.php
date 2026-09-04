@extends('layouts.app')

@section('title', 'Record Flock Event - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Record Flock Event</h1>
        <p class="text-sm text-gray-600">Record mortality, culls, or slaughter for a batch</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <a href="{{ route('poultry.forms.hub') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('poultry.forms.flock-record.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="poultry_batch_id" class="block text-sm font-medium text-gray-700">Batch <span class="text-red-500">*</span></label>
                <select name="poultry_batch_id" id="poultry_batch_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                    <option value="">-- Select Batch --</option>
                    @foreach($batches ?? [] as $batch)
                    <option value="{{ $batch->id }}" data-remaining="{{ $batch->remaining_flock }}" data-avg-weight="{{ $batch->current_average_weight }}" {{ old('poultry_batch_id') == $batch->id ? 'selected' : '' }}>
                        {{ $batch->batch_id }} - {{ $batch->name }} ({{ $batch->remaining_flock }} birds, avg: {{ number_format($batch->current_average_weight, 2) }} kg)
                    </option>
                    @endforeach
                </select>
                @error('poultry_batch_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div id="batchInfo" class="hidden p-3 bg-blue-50 rounded-lg border border-blue-200">
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div><span class="text-gray-600">Remaining birds:</span> <span id="remainingDisplay" class="font-bold">0</span></div>
                    <div><span class="text-gray-600">Avg. weight:</span> <span id="avgWeightDisplay" class="font-bold">0.000</span> kg</div>
                </div>
            </div>

            <div>
                <label for="date" class="block text-sm font-medium text-gray-700">Date <span class="text-red-500">*</span></label>
                <input type="date" name="date" id="date" value="{{ old('date', now()->toDateString()) }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                @error('date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="mortality" class="block text-sm font-medium text-gray-700">Mortality</label>
                    <input type="number" name="mortality" id="mortality" value="{{ old('mortality', 0) }}" min="0" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    @error('mortality') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="culls" class="block text-sm font-medium text-gray-700">Culls</label>
                    <input type="number" name="culls" id="culls" value="{{ old('culls', 0) }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    @error('culls') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="slaughter" class="block text-sm font-medium text-gray-700">Slaughter</label>
                    <input type="number" name="slaughter" id="slaughter" value="{{ old('slaughter', 0) }}" min="0" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    @error('slaughter') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- NEW: Average Weight of Slaughtered Birds -->
            <div id="slaughter_weight_container" class="hidden">
                <label for="slaughter_avg_weight" class="block text-sm font-medium text-gray-700">Average Weight of Slaughtered Birds (kg) <span class="text-red-500">*</span></label>
                <input type="number" name="slaughter_avg_weight" id="slaughter_avg_weight" step="0.001" min="0.001" value="{{ old('slaughter_avg_weight') }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="e.g. 2.5">
                <p class="mt-1 text-xs text-gray-500">Enter the actual average weight of the birds being slaughtered (e.g., customer requested a specific weight). Leave blank to use the batch average.</p>
                @error('slaughter_avg_weight') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Optional notes...">{{ old('notes') }}</textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('poultry.forms.hub') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">Save Record</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const batchSelect = document.getElementById('poultry_batch_id');
    const batchInfo = document.getElementById('batchInfo');
    const remainingDisplay = document.getElementById('remainingDisplay');
    const avgWeightDisplay = document.getElementById('avgWeightDisplay');
    const slaughterInput = document.getElementById('slaughter');
    const slaughterWeightContainer = document.getElementById('slaughter_weight_container');
    const slaughterWeightInput = document.getElementById('slaughter_avg_weight');

    function updateBatchInfo() {
        const option = batchSelect.options[batchSelect.selectedIndex];
        const remaining = option.dataset.remaining;
        const avgWeight = option.dataset.avgWeight;
        if (remaining) {
            remainingDisplay.textContent = remaining;
            avgWeightDisplay.textContent = avgWeight ? parseFloat(avgWeight).toFixed(3) : '0.000';
            batchInfo.classList.remove('hidden');
        } else {
            batchInfo.classList.add('hidden');
        }
    }

    function toggleSlaughterWeight() {
        const slaughterCount = parseInt(slaughterInput.value) || 0;
        if (slaughterCount > 0) {
            slaughterWeightContainer.classList.remove('hidden');
            slaughterWeightInput.setAttribute('required', 'required');
        } else {
            slaughterWeightContainer.classList.add('hidden');
            slaughterWeightInput.removeAttribute('required');
        }
    }

    batchSelect.addEventListener('change', updateBatchInfo);
    slaughterInput.addEventListener('input', toggleSlaughterWeight);

    updateBatchInfo();
    toggleSlaughterWeight();
});
</script>
@endpush
@endsection