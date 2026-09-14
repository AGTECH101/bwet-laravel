@extends('layouts.app')

@section('title', 'Poultry Price Calculator - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Poultry Price Calculator</h1>
        <p class="text-sm text-gray-600">Calculate the selling price for a customer order using the batch's cost basis.</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Current Settings Summary -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
            <div class="ml-3 text-sm text-blue-800">
                <p><strong>Current defaults:</strong>
                    Profit margin: <strong>{{ number_format($defaultProfitMargin, 1) }}%</strong> ·
                    Dress percentage: <strong>{{ number_format($defaultDressPercentage, 1) }}%</strong>
                </p>
                <p class="mt-1 text-xs">These come from System Settings. You can override the profit margin below for a single calculation.</p>
            </div>
        </div>
    </div>

    <!-- Input Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form id="priceCalculatorForm" class="space-y-6">
            @csrf

            <div>
                <label for="batch_id" class="block text-sm font-medium text-gray-700">
                    Select Batch <span class="text-red-500">*</span>
                </label>
                <select name="batch_id" id="batch_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" required onchange="updateBatchSummary()">
                    <option value="" data-count="0" data-cost="0" data-weight="0">-- Select Active Batch --</option>
                    @foreach($batches as $batch)
                        <option
                            value="{{ $batch->id }}"
                            data-batch-id="{{ $batch->batch_id }}"
                            data-name="{{ $batch->name }}"
                            data-count="{{ $batch->current_count }}"
                            data-cost="{{ number_format((float) $batch->current_average_cost, 2, '.', '') }}"
                            data-weight="{{ number_format((float) $batch->current_average_weight, 3, '.', '') }}"
                            {{ $selectedBatch && $selectedBatch->id == $batch->id ? 'selected' : '' }}>
                            {{ $batch->batch_id }} — {{ $batch->name }} ({{ $batch->current_count }} birds · ₦{{ number_format($batch->current_average_cost, 2) }}/bird · {{ number_format($batch->current_average_weight, 3) }} kg)
                        </option>
                    @endforeach
                </select>
                @error('batch_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <!-- Live Batch Summary -->
            <div id="batchSummary" class="hidden rounded-lg bg-gray-50 border border-gray-200 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-800">
                        <i class="fas fa-layer-group text-primary-600 mr-1"></i>
                        Selected Batch Summary
                    </h4>
                    <span class="text-xs text-gray-500">Auto-updates with your selection</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <p class="text-xs text-gray-500">Birds Remaining</p>
                        <p class="text-lg font-bold text-gray-900 mt-1" id="summary_count">—</p>
                    </div>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <p class="text-xs text-gray-500">Avg Cost / Bird</p>
                        <p class="text-lg font-bold text-emerald-700 mt-1">₦<span id="summary_cost">—</span></p>
                    </div>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <p class="text-xs text-gray-500">Avg Live Weight</p>
                        <p class="text-lg font-bold text-blue-700 mt-1"><span id="summary_weight">—</span> kg</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="customer_bird_weight" class="block text-sm font-medium text-gray-700">
                        Customer Bird Weight (kg) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.001" min="0.001" max="20"
                           id="customer_bird_weight" name="customer_bird_weight"
                           value="{{ old('customer_bird_weight', 2.5) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                    <p class="mt-1 text-xs text-gray-500">Weight of the bird the customer is requesting.</p>
                </div>

                <div>
                    <label for="mode_weight" class="block text-sm font-medium text-gray-700">
                        Mode Weight (kg) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.001" min="0.001" max="20"
                           id="mode_weight" name="mode_weight"
                           value="{{ old('mode_weight', 2.5) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                    <p class="mt-1 text-xs text-gray-500">The most common/dominant weight in the batch (measured on-farm).</p>
                </div>

                <div>
                    <label for="profit_margin" class="block text-sm font-medium text-gray-700">
                        Profit Margin (%)
                    </label>
                    <input type="number" step="0.1" min="0" max="1000"
                           id="profit_margin" name="profit_margin"
                           value="{{ old('profit_margin', $defaultProfitMargin) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <p class="mt-1 text-xs text-gray-500">Leave as-is to use the system default.</p>
                </div>
            </div>

            <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-700 border border-gray-200">
                <p class="font-medium mb-1">Formula:</p>
                <code class="text-xs">
                    cost_scaled = (customer_weight ÷ mode_weight) × batch_avg_cost<br>
                    price_per_bird = cost_scaled × (1 + margin ÷ 100)<br>
                    price_per_kg = price_per_bird ÷ (customer_weight × dress% ÷ 100)
                </code>
            </div>

            <div class="flex justify-end">
                <button type="submit" id="calculateBtn" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50">
                    <i class="fas fa-calculator mr-2"></i> Calculate Price
                </button>
            </div>
        </form>
    </div>

    <!-- Results -->
    <div id="resultContainer" class="hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">
            <h3 class="text-lg font-semibold text-gray-900">Calculated Price</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-lg bg-green-50 p-4 border border-green-200">
                    <p class="text-sm text-gray-600">Price Per Bird</p>
                    <p id="selling_price_per_bird" class="text-2xl font-bold text-gray-900 mt-1">₦0.00</p>
                </div>
                <div class="rounded-lg bg-blue-50 p-4 border border-blue-200">
                    <p class="text-sm text-gray-600">Price Per kg (dressed)</p>
                    <p id="selling_price_per_kg" class="text-2xl font-bold text-gray-900 mt-1">₦0.00</p>
                </div>
                <div class="rounded-lg bg-purple-50 p-4 border border-purple-200">
                    <p class="text-sm text-gray-600">Price Per Carton (10 kg)</p>
                    <p id="selling_price_per_carton" class="text-2xl font-bold text-gray-900 mt-1">₦0.00</p>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Breakdown</h4>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                    <div class="flex justify-between md:block">
                        <span class="text-gray-500">Batch:</span>
                        <span id="batch_name" class="font-medium text-gray-900 md:block">-</span>
                    </div>
                    <div class="flex justify-between md:block">
                        <span class="text-gray-500">Avg Cost / Bird:</span>
                        <span class="font-medium text-gray-900 md:block">₦<span id="current_avg_cost">0.00</span></span>
                    </div>
                    <div class="flex justify-between md:block">
                        <span class="text-gray-500">Cost Scaled:</span>
                        <span class="font-medium text-gray-900 md:block">₦<span id="cost_scaled">0.00</span></span>
                    </div>
                    <div class="flex justify-between md:block">
                        <span class="text-gray-500">Margin Applied:</span>
                        <span class="font-medium text-gray-900 md:block"><span id="profit_margin_display">0</span>%</span>
                    </div>
                    <div class="flex justify-between md:block">
                        <span class="text-gray-500">Dress %:</span>
                        <span class="font-medium text-gray-900 md:block"><span id="dress_percentage_display">0</span>%</span>
                    </div>
                </div>
            </div>

            <div class="text-xs text-gray-500 bg-gray-50 p-3 rounded-lg">
                <i class="fas fa-lightbulb text-yellow-500 mr-1"></i>
                <strong>Tip:</strong> Adjust the profit margin above and click Calculate again to see the impact on price.
            </div>
        </div>
    </div>

    <!-- Error -->
    <div id="calculator_error" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <i class="fas fa-exclamation-triangle mr-1"></i> <span id="error_message"></span>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('priceCalculatorForm');
    const resultContainer = document.getElementById('resultContainer');
    const errorBox = document.getElementById('calculator_error');
    const errorMessage = document.getElementById('error_message');
    const calculateBtn = document.getElementById('calculateBtn');
    const batchSelect = document.getElementById('batch_id');
    const batchSummary = document.getElementById('batchSummary');

    // ============================================================
    // Live batch summary
    // ============================================================
    window.updateBatchSummary = function () {
        const opt = batchSelect.options[batchSelect.selectedIndex];
        if (!opt || !opt.value) {
            batchSummary.classList.add('hidden');
            return;
        }

        document.getElementById('summary_count').textContent = Number(opt.dataset.count || 0).toLocaleString();
        document.getElementById('summary_cost').textContent = Number(opt.dataset.cost || 0).toFixed(2);
        document.getElementById('summary_weight').textContent = Number(opt.dataset.weight || 0).toFixed(3);
        batchSummary.classList.remove('hidden');

        // Prefill mode_weight from the batch avg weight if the user hasn't typed anything custom yet
        const modeWeightInput = document.getElementById('mode_weight');
        if (modeWeightInput && !modeWeightInput.dataset.userEdited) {
            modeWeightInput.value = Number(opt.dataset.weight || 2.5).toFixed(3);
        }
    };

    // Mark mode_weight as user-edited once they type into it
    document.getElementById('mode_weight').addEventListener('input', function () {
        this.dataset.userEdited = '1';
    });

    // Initialize on page load
    updateBatchSummary();

    // ============================================================
    // Submit calculator form
    // ============================================================
    form.addEventListener('submit', function (event) {
        event.preventDefault();

        resultContainer.classList.add('hidden');
        errorBox.classList.add('hidden');
        calculateBtn.disabled = true;
        calculateBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Calculating...';

        const formData = new FormData(form);

        fetch('{{ route('poultry.forms.price-calculator.calculate') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(async (response) => {
            const text = await response.text();
            let data;
            try { data = JSON.parse(text); } catch (e) {
                throw new Error('Server returned an unexpected response. Please refresh and try again.');
            }
            if (!response.ok) {
                throw new Error(data.error || 'Unable to calculate the price.');
            }
            return data;
        })
        .then(data => {
            document.getElementById('selling_price_per_bird').textContent = '₦' + Number(data.selling_price_per_bird || 0).toFixed(2);
            document.getElementById('selling_price_per_kg').textContent = '₦' + Number(data.selling_price_per_kg || 0).toFixed(2);
            document.getElementById('selling_price_per_carton').textContent = '₦' + Number(data.selling_price_per_carton || 0).toFixed(2);

            document.getElementById('batch_name').textContent = data.batch_name || '-';
            document.getElementById('current_avg_cost').textContent = Number(data.current_avg_cost || 0).toFixed(2);
            document.getElementById('cost_scaled').textContent = Number(data.cost_scaled || 0).toFixed(2);
            document.getElementById('profit_margin_display').textContent = Number(data.profit_margin || 0).toFixed(1);
            document.getElementById('dress_percentage_display').textContent = Number(data.dress_percentage || 0).toFixed(1);

            resultContainer.classList.remove('hidden');
            resultContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch((error) => {
            errorMessage.textContent = error.message;
            errorBox.classList.remove('hidden');
        })
        .finally(() => {
            calculateBtn.disabled = false;
            calculateBtn.innerHTML = '<i class="fas fa-calculator mr-2"></i> Calculate Price';
        });
    });
});
</script>
@endpush
@endsection