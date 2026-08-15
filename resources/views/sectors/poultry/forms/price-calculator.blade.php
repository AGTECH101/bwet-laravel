@extends('layouts.app')

@section('title', 'Poultry Price Calculator - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Poultry Price Calculator</h1>
        <p class="text-sm text-gray-600">Calculate a poultry selling price using batch production cost, average weight, and mode weight.</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form id="poultryPriceCalculatorForm" class="space-y-6">
            @csrf

            <div>
                <label for="selected_batch" class="block text-sm font-medium text-gray-700">Selected batch</label>
                <select id="selected_batch" name="selected_batch" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Select a batch</option>
                    @foreach($batches as $batch)
                        <option value="{{ $batch->batch_id }}" {{ $selectedBatch && $selectedBatch->batch_id == $batch->batch_id ? 'selected' : '' }}>
                            {{ $batch->batch_id }} - {{ $batch->name ?? 'Batch' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="cost_of_production" class="block text-sm font-medium text-gray-700">Cost of Production</label>
                    <input type="number" step="0.01" min="0" id="cost_of_production" name="cost_of_production" value="{{ $selectedBatch ? $selectedBatch->total_expenses : 0 }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label for="current_average_weight" class="block text-sm font-medium text-gray-700">Current Average Weight</label>
                    <input type="number" step="0.01" min="0.01" id="current_average_weight" name="current_average_weight" value="{{ $selectedBatch ? $selectedBatch->getCurrentAverageWeight() : 0 }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label for="mod_weight" class="block text-sm font-medium text-gray-700">Mode Weight</label>
                    <input type="number" step="0.01" min="0.01" id="mod_weight" name="mod_weight" value="1" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                </div>
            </div>

            <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-700 border border-gray-200">
                Formula: (cost_of_production × current_average_weight) ÷ mod_weight
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                    <i class="fas fa-calculator mr-2"></i> Calculate Price
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Calculated Result</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="rounded-lg bg-gray-50 p-4">
                <div class="text-gray-500">Calculated Price</div>
                <div id="calculated_price" class="mt-2 text-2xl font-bold text-gray-900">₦0.00</div>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <div class="text-gray-500">Selected Batch</div>
                <div id="selected_batch_label" class="mt-2 text-lg font-bold text-gray-900">-</div>
            </div>
        </div>
        <div id="calculator_error" class="mt-4 hidden rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('poultryPriceCalculatorForm');
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
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(data.error || 'Unable to calculate the price right now.');
                }
                return data;
            })
            .then(data => {
                document.getElementById('calculated_price').textContent = '₦' + Number(data.calculated_price || 0).toFixed(2);
                document.getElementById('selected_batch_label').textContent = data.batch || '-';
            })
            .catch((error) => {
                errorBox.textContent = error.message || 'Unable to calculate the price right now.';
                errorBox.classList.remove('hidden');
            });
        });
    });
</script>
@endpush
@endsection
