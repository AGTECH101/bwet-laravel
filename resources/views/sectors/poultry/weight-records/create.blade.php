@extends('layouts.app')

@section('title', 'Record Weight - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Record Weight</h1>
        <p class="text-sm text-gray-600">Capture individual bird weights for accurate batch monitoring.</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form method="POST" id="weightForm" action="{{ route('poultry.forms.weight-record.store') }}" class="p-6 space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="poultry_batch_id" class="block text-sm font-medium text-gray-700 mb-2">Batch <span class="text-red-500">*</span></label>
                    <select name="poultry_batch_id" id="poultry_batch_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" required>
                        <option value="">-- Select Batch --</option>
                        @foreach($batches ?? [] as $item)
                            <option value="{{ $item->id }}" data-remaining="{{ $item->remaining_flock ?? 0 }}" data-age="{{ $item->current_age_days ?? 0 }}" {{ old('poultry_batch_id', $batch?->id) == $item->id ? 'selected' : '' }}>
                                {{ $item->batch_id }} - {{ $item->name }} ({{ $item->remaining_flock ?? 0 }} birds)
                            </option>
                        @endforeach
                    </select>
                    @error('poultry_batch_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="date" id="date" value="{{ old('date', now()->toDateString()) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" required>
                    @error('date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div id="batchInfoBox" class="hidden bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-600">Batch Age</p>
                        <p class="text-lg font-semibold text-gray-900" id="batchAge">-</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Remaining Birds</p>
                        <p class="text-lg font-semibold text-gray-900" id="batchRemaining">-</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Required Sample</p>
                        <p class="text-lg font-semibold text-green-600" id="requiredSample">-</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600" id="sampleValidation">Enter weights below</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Individual Bird Weights (kg)</h2>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 mb-4">
                    @for ($i = 1; $i <= 10; $i++)
                        <div>
                            <label for="weight_{{ $i }}" class="block text-xs font-medium text-gray-600 mb-1">Bird {{ $i }}</label>
                            <input type="number"
                                   name="individual_weights[]"
                                   id="weight_{{ $i }}"
                                   step="0.001"
                                   min="0.001"
                                   max="5"
                                   value="{{ old('individual_weights.' . ($i - 1)) }}"
                                   placeholder="0.000"
                                   class="bird-weight-input w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>
                    @endfor
                </div>
            </div>

            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4 border border-green-200">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-gray-600">Birds Weighed</p>
                        <p class="text-2xl font-bold text-gray-900" id="birdsCount">0</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Total Weight</p>
                        <p class="text-2xl font-bold text-gray-900"><span id="totalWeight">0.000</span> kg</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Average Weight</p>
                        <p class="text-2xl font-bold text-blue-600"><span id="avgWeight">0.000</span> kg</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">CV</p>
                        <p class="text-2xl font-bold" id="cvPercent"><span id="cvValue">0.00</span>%</p>
                    </div>
                </div>
                <div id="cvStatusBox" class="mt-3 p-2 rounded text-sm font-medium hidden">
                    <span id="cvStatusText"></span>
                </div>
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                <textarea name="notes" id="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="e.g., High variation due to feeder access issues">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-3 pt-6 border-t border-gray-200">
                <button type="submit" class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors">
                    Save Weight Record
                </button>
                <a href="{{ $batch ? route('poultry.batches.show', $batch) : route('poultry.batches.index') }}" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-900 font-medium py-2.5 px-4 rounded-lg transition-colors text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const weightInputs = document.querySelectorAll('.bird-weight-input');

    function updateBatchInfo() {
        const select = document.getElementById('poultry_batch_id');
        const option = select.options[select.selectedIndex];

        if (select.value && option) {
            const remaining = Number(option.dataset.remaining || 0);
            const age = Number(option.dataset.age || 0);

            document.getElementById('batchAge').textContent = age + ' days';
            document.getElementById('batchRemaining').textContent = remaining + ' birds';

            const required = Math.min(Math.max(Math.ceil(remaining * 0.10), 5), 10);
            document.getElementById('requiredSample').textContent = required + ' birds';
            document.getElementById('sampleValidation').textContent = 'Enter weights below';
            document.getElementById('batchInfoBox').classList.remove('hidden');
        } else {
            document.getElementById('batchInfoBox').classList.add('hidden');
        }
    }

    function calculateStats() {
        const weights = [];

        weightInputs.forEach((input) => {
            const value = input.value.trim();
            if (value !== '') {
                const weight = Number.parseFloat(value.replace(',', '.'));
                if (!Number.isNaN(weight) && weight > 0) {
                    weights.push(weight);
                }
            }
        });

        document.getElementById('birdsCount').textContent = weights.length;

        if (weights.length === 0) {
            document.getElementById('totalWeight').textContent = '0.000';
            document.getElementById('avgWeight').textContent = '0.000';
            document.getElementById('cvValue').textContent = '0.00';
            document.getElementById('cvPercent').className = 'text-2xl font-bold text-gray-400';
            document.getElementById('cvStatusBox').classList.add('hidden');
            return;
        }

        const total = weights.reduce((sum, weight) => sum + weight, 0);
        const average = total / weights.length;

        document.getElementById('totalWeight').textContent = total.toFixed(3);
        document.getElementById('avgWeight').textContent = average.toFixed(3);

        let cv = 0;
        if (weights.length >= 2) {
            const variance = weights.reduce((sum, weight) => sum + ((weight - average) ** 2), 0) / weights.length;
            const stdDev = Math.sqrt(variance);
            cv = (stdDev / average) * 100;
        }

        document.getElementById('cvValue').textContent = cv.toFixed(2);

        const statusBox = document.getElementById('cvStatusBox');
        const statusText = document.getElementById('cvStatusText');
        const cvPercent = document.getElementById('cvPercent');

        statusBox.classList.remove('hidden');

        if (cv >= 15) {
            cvPercent.className = 'text-2xl font-bold text-red-600';
            statusBox.className = 'mt-3 p-2 rounded text-sm font-medium bg-red-100 text-red-800';
            statusText.textContent = 'High variation detected. Re-measurement is required before saving.';
        } else if (cv >= 12) {
            cvPercent.className = 'text-2xl font-bold text-yellow-600';
            statusBox.className = 'mt-3 p-2 rounded text-sm font-medium bg-yellow-100 text-yellow-800';
            statusText.textContent = 'Caution - monitor closely.';
        } else if (cv >= 10) {
            cvPercent.className = 'text-2xl font-bold text-blue-600';
            statusBox.className = 'mt-3 p-2 rounded text-sm font-medium bg-blue-100 text-blue-800';
            statusText.textContent = 'Good variation.';
        } else {
            cvPercent.className = 'text-2xl font-bold text-green-600';
            statusBox.className = 'mt-3 p-2 rounded text-sm font-medium bg-green-100 text-green-800';
            statusText.textContent = 'Excellent uniformity.';
        }
    }

    weightInputs.forEach((input) => {
        input.addEventListener('input', calculateStats);
        input.addEventListener('change', calculateStats);
    });

    document.getElementById('weightForm').addEventListener('submit', function (event) {
        let hasValidWeight = false;
        const values = [];

        weightInputs.forEach((input) => {
            const value = input.value.trim();
            if (value !== '') {
                const weight = Number.parseFloat(value.replace(',', '.'));
                if (!Number.isNaN(weight) && weight > 0) {
                    hasValidWeight = true;
                    values.push(weight);
                }
            }
        });

        if (!hasValidWeight) {
            event.preventDefault();
            alert('Please enter at least one valid bird weight between 0.001 and 5 kg.');
            return false;
        }

        if (values.length >= 2) {
            const mean = values.reduce((sum, value) => sum + value, 0) / values.length;
            const variance = values.reduce((sum, value) => sum + ((value - mean) ** 2), 0) / values.length;
            const stdDev = Math.sqrt(variance);
            const cv = (stdDev / mean) * 100;

            if (cv >= 15) {
                event.preventDefault();
                alert('High weight variation detected (CV >= 15%). Please re-take the sample before saving.');
                return false;
            }
        }

        const batchSelect = document.getElementById('poultry_batch_id');
        if (!batchSelect.value) {
            event.preventDefault();
            alert('Please select a batch before saving.');
            return false;
        }

        return true;
    });

    document.addEventListener('DOMContentLoaded', function () {
        const batchSelect = document.getElementById('poultry_batch_id');
        if (batchSelect) {
            batchSelect.addEventListener('change', updateBatchInfo);
        }

        updateBatchInfo();
        calculateStats();
    });
</script>
@endpush
@endsection
