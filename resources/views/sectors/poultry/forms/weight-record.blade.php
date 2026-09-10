@extends('layouts.app')

@section('title', 'Record Weight - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Record Weight</h1>
        <p class="text-sm text-gray-600">Record individual bird weights for accurate batch monitoring</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <a href="{{ route('poultry.forms.hub') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('poultry.forms.weight-record.store') }}" id="weightForm" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="poultry_batch_id" class="block text-sm font-medium text-gray-700">Batch <span class="text-red-500">*</span></label>
                    <select name="poultry_batch_id" id="poultry_batch_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" required onchange="updateBatchInfo()">
                        <option value="">-- Select Batch --</option>
                        @foreach($batches ?? [] as $batch)
                        <option value="{{ $batch->id }}" data-remaining="{{ $batch->remaining_flock }}" data-age="{{ $batch->current_age_days }}">
                            {{ $batch->batch_id }} - {{ $batch->name }} ({{ $batch->remaining_flock }} birds)
                        </option>
                        @endforeach
                    </select>
                    @error('poultry_batch_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="date" id="date" value="{{ old('date', now()->toDateString()) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" required>
                    @error('date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div id="batchInfoBox" class="hidden bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="grid grid-cols-2 gap-4">
                    <div><p class="text-xs text-gray-600">Batch Age</p><p class="text-lg font-semibold text-gray-900" id="batchAge">-</p></div>
                    <div><p class="text-xs text-gray-600">Remaining Birds</p><p class="text-lg font-semibold text-gray-900" id="batchRemaining">-</p></div>
                    <div><p class="text-xs text-gray-600">Required Sample</p><p class="text-lg font-semibold text-green-600" id="requiredSample">-</p></div>
                    <div><p class="text-xs text-gray-600" id="sampleValidation">Enter weights below</p></div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Individual Bird Weights (kg)</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                    @for($i = 1; $i <= 10; $i++)
                    <div>
                        <label for="weight_{{ $i }}" class="block text-xs font-medium text-gray-600 mb-1">Bird {{ $i }}</label>
                        <input type="number" name="weight_{{ $i }}" id="weight_{{ $i }}" step="0.001" min="0.001" max="5" placeholder="0.000" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 bird-weight-input">
                    </div>
                    @endfor
                </div>
            </div>

            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4 border border-green-200">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div><p class="text-xs text-gray-600">Birds Weighed</p><p class="text-2xl font-bold text-gray-900" id="birdsCount">0</p></div>
                    <div><p class="text-xs text-gray-600">Total Weight</p><p class="text-2xl font-bold text-gray-900"><span id="totalWeight">0.000</span> kg</p></div>
                    <div><p class="text-xs text-gray-600">Average Weight</p><p class="text-2xl font-bold text-blue-600"><span id="avgWeight">0.000</span> kg</p></div>
                    <div><p class="text-xs text-gray-600">CV</p><p class="text-2xl font-bold" id="cvPercent"><span id="cvValue">0.00</span>%</p></div>
                </div>
                <div id="cvStatusBox" class="mt-3 p-2 rounded text-sm font-medium hidden"><span id="cvStatusText"></span></div>
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" id="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="e.g., High variation due to feeder access issues">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-3 pt-6 border-t border-gray-200">
                <button type="submit" class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors">
                    <i class="fas fa-save mr-2"></i> Save Weight Record
                </button>
                <a href="{{ route('poultry.forms.hub') }}" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-900 font-medium py-2.5 px-4 rounded-lg transition-colors text-center">
                    <i class="fas fa-times mr-2"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.bird-weight-input');
    const batchSelect = document.getElementById('poultry_batch_id');

    function updateBatchInfo() {
        const option = batchSelect.options[batchSelect.selectedIndex];
        if (batchSelect.value) {
            document.getElementById('batchAge').textContent = option.dataset.age + ' days';
            document.getElementById('batchRemaining').textContent = option.dataset.remaining + ' birds';
            const remaining = parseInt(option.dataset.remaining) || 0;
            const required = Math.min(Math.max(Math.ceil(remaining * 0.10), 5), 10);
            document.getElementById('requiredSample').textContent = required + ' birds';
            document.getElementById('batchInfoBox').classList.remove('hidden');
        } else {
            document.getElementById('batchInfoBox').classList.add('hidden');
        }
    }

    function calculateStats() {
        let weights = [];
        inputs.forEach(input => {
            const val = parseFloat(input.value);
            if (!isNaN(val) && val > 0) weights.push(val);
        });
        const count = weights.length;
        document.getElementById('birdsCount').textContent = count;
        if (count === 0) {
            document.getElementById('totalWeight').textContent = '0.000';
            document.getElementById('avgWeight').textContent = '0.000';
            document.getElementById('cvValue').textContent = '0.00';
            document.getElementById('cvStatusBox').classList.add('hidden');
            return;
        }
        const total = weights.reduce((a,b) => a + b, 0);
        const avg = total / count;
        document.getElementById('totalWeight').textContent = total.toFixed(3);
        document.getElementById('avgWeight').textContent = avg.toFixed(3);

        let cv = 0;
        if (count >= 2) {
            const variance = weights.reduce((s, w) => s + (w - avg) ** 2, 0) / count;
            const stddev = Math.sqrt(variance);
            cv = (stddev / avg) * 100;
        }
        document.getElementById('cvValue').textContent = cv.toFixed(2);
        const statusBox = document.getElementById('cvStatusBox');
        const statusText = document.getElementById('cvStatusText');
        const cvPercent = document.getElementById('cvPercent');
        statusBox.classList.remove('hidden');
        if (cv >= 15) {
            cvPercent.className = 'text-2xl font-bold text-red-600';
            statusBox.className = 'mt-3 p-2 rounded text-sm font-medium bg-red-100 text-red-800';
            statusText.textContent = '⚠️ High variation - Check feeding and health';
        } else if (cv >= 12) {
            cvPercent.className = 'text-2xl font-bold text-yellow-600';
            statusBox.className = 'mt-3 p-2 rounded text-sm font-medium bg-yellow-100 text-yellow-800';
            statusText.textContent = '⚠️ Caution - Monitor closely';
        } else if (cv >= 10) {
            cvPercent.className = 'text-2xl font-bold text-blue-600';
            statusBox.className = 'mt-3 p-2 rounded text-sm font-medium bg-blue-100 text-blue-800';
            statusText.textContent = '✓ Good variation';
        } else {
            cvPercent.className = 'text-2xl font-bold text-green-600';
            statusBox.className = 'mt-3 p-2 rounded text-sm font-medium bg-green-100 text-green-800';
            statusText.textContent = '✅ Excellent uniformity';
        }
    }

    inputs.forEach(input => input.addEventListener('input', calculateStats));
    batchSelect.addEventListener('change', updateBatchInfo);
    updateBatchInfo();
    calculateStats();

    document.getElementById('weightForm').addEventListener('submit', function(e) {
        let hasValid = false;
        inputs.forEach(input => {
            const val = parseFloat(input.value);
            if (!isNaN(val) && val > 0) hasValid = true;
        });
        if (!hasValid) {
            e.preventDefault();
            alert('Please enter at least one valid bird weight.');
        }
    });
});
</script>
@endpush
@endsection