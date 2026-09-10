@extends('layouts.app')

@section('title', 'Poultry Price Calculator - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Poultry Price Calculator</h1>
        <p class="text-sm text-gray-600">Calculate selling price based on batch cost, customer weight, and mode weight.</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form id="priceCalculatorForm" class="space-y-6">
            @csrf
            <div>
                <label for="batch_id" class="block text-sm font-medium text-gray-700">Select Batch</label>
                <select name="batch_id" id="batch_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Select Active Batch --</option>
                    @foreach($batches as $batch)
                        <option value="{{ $batch->id }}" {{ $selectedBatch && $selectedBatch->id == $batch->id ? 'selected' : '' }}>
                            {{ $batch->batch_id }} - {{ $batch->name }} (avg cost: ₦{{ number_format($batch->current_average_cost, 2) }}, count: {{ $batch->current_count }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="customer_bird_weight" class="block text-sm font-medium text-gray-700">Customer Bird Weight (kg)</label>
                    <input type="number" step="0.001" min="0.001" id="customer_bird_weight" name="customer_bird_weight" value="{{ old('customer_bird_weight', 2.5) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                </div>
                <div>
                    <label for="mode_weight" class="block text-sm font-medium text-gray-700">Batch Mode Weight (kg)</label>
                    <input type="number" step="0.001" min="0.001" id="mode_weight" name="mode_weight" value="{{ old('mode_weight', 2.5) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                    <p class="mt-1 text-xs text-gray-500">The most frequent or dominant weight in the batch.</p>
                </div>
                <div>
                    <label for="profit_margin" class="block text-sm font-medium text-gray-700">Target Margin (%)</label>
                    <input type="number" step="0.1" min="0" max="100" id="profit_margin" name="profit_margin" value="{{ $defaultProfitMargin }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                    <i class="fas fa-calculator mr-2"></i> Calculate Price
                </button>
            </div>
        </form>

        <div id="resultContainer" class="mt-6 hidden">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Calculated Price</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-lg bg-green-50 p-4 border border-green-200">
                    <p class="text-sm text-gray-600">Per Bird</p>
                    <p id="selling_price_per_bird" class="text-2xl font-bold text-gray-900">₦0.00</p>
                </div>
                <div class="rounded-lg bg-blue-50 p-4 border border-blue-200">
                    <p class="text-sm text-gray-600">Per kg (dressed)</p>
                    <p id="selling_price_per_kg" class="text-2xl font-bold text-gray-900">₦0.00</p>
                </div>
                <div class="rounded-lg bg-purple-50 p-4 border border-purple-200">
                    <p class="text-sm text-gray-600">Per Carton (10kg)</p>
                    <p id="selling_price_per_carton" class="text-2xl font-bold text-gray-900">₦0.00</p>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-500 border-t border-gray-200 pt-4">
                <p><strong>Batch:</strong> <span id="batch_name">-</span></p>
                <p><strong>Current Avg Cost per Bird:</strong> ₦<span id="current_avg_cost">0.00</span></p>
                <p><strong>Cost Scaled to Customer Weight:</strong> ₦<span id="cost_scaled">0.00</span></p>
                <p><strong>Margin Applied:</strong> <span id="profit_margin_display">0</span>%</p>
            </div>
            <div id="calculator_error" class="mt-4 hidden rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('priceCalculatorForm');
    const resultContainer = document.getElementById('resultContainer');
    const errorBox = document.getElementById('calculator_error');

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        errorBox.classList.add('hidden');
        errorBox.textContent = '';

        const formData = new FormData(form);

        fetch('{{ route('poultry.forms.price-calculator.calculate') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(async response => {
            const data = await response.json();
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
            resultContainer.classList.remove('hidden');
        })
        .catch((error) => {
            errorBox.textContent = error.message || 'Unable to calculate the price.';
            errorBox.classList.remove('hidden');
            resultContainer.classList.add('hidden');
        });
    });
});
</script>
@endpush
@endsection