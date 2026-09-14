@extends('layouts.app')

@section('title', 'Price Calculator - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">General Price Calculator</h1>
        <p class="text-sm text-gray-600">Calculate a recommended selling price from cost and weight.</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form id="generalPriceForm" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="cost_per_bird" class="block text-sm font-medium text-gray-700">
                        Cost per Bird (₦) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0"
                           id="cost_per_bird" name="cost_per_bird"
                           value="{{ old('cost_per_bird', 0) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                </div>

                <div>
                    <label for="dressed_weight" class="block text-sm font-medium text-gray-700">
                        Dressed Weight (kg) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.001" min="0.001"
                           id="dressed_weight" name="dressed_weight"
                           value="{{ old('dressed_weight', 1.5) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                </div>

                <div>
                    <label for="target_margin" class="block text-sm font-medium text-gray-700">
                        Target Margin (%)
                    </label>
                    <input type="number" step="0.1" min="0" max="1000"
                           id="target_margin" name="target_margin"
                           value="{{ old('target_margin', $profitMargin ?? 20) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" id="generalCalcBtn" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50">
                    <i class="fas fa-calculator mr-2"></i> Calculate
                </button>
            </div>
        </form>
    </div>

    <div id="generalResults" class="hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Result</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-sm text-gray-600">Cost / kg</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">₦<span id="gc_cost_per_kg">0.00</span></p>
                </div>
                <div class="rounded-lg bg-yellow-50 p-4 border border-yellow-200">
                    <p class="text-sm text-gray-600">Minimum Price / kg</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">₦<span id="gc_minimum">0.00</span></p>
                </div>
                <div class="rounded-lg bg-green-50 p-4 border border-green-200">
                    <p class="text-sm text-gray-600">Suggested Price / kg</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">₦<span id="gc_suggested">0.00</span></p>
                </div>
            </div>
        </div>
    </div>

    <div id="gc_error" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <i class="fas fa-exclamation-triangle mr-1"></i> <span id="gc_error_message"></span>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('generalPriceForm');
    const results = document.getElementById('generalResults');
    const errorBox = document.getElementById('gc_error');
    const errorMessage = document.getElementById('gc_error_message');
    const calcBtn = document.getElementById('generalCalcBtn');

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        results.classList.add('hidden');
        errorBox.classList.add('hidden');
        calcBtn.disabled = true;
        calcBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Calculating...';

        const formData = new FormData(form);

        fetch('{{ route('price-calculator.calculate') }}', {
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
                throw new Error('Server returned an unexpected response.');
            }
            if (!response.ok) {
                throw new Error(data.error || data.message || 'Calculation failed.');
            }
            return data;
        })
        .then(data => {
            document.getElementById('gc_cost_per_kg').textContent = Number(data.cost_per_kg || 0).toFixed(2);
            document.getElementById('gc_minimum').textContent = Number(data.minimum_price_per_kg || 0).toFixed(2);
            document.getElementById('gc_suggested').textContent = Number(data.suggested_price_per_kg || 0).toFixed(2);
            results.classList.remove('hidden');
        })
        .catch(err => {
            errorMessage.textContent = err.message;
            errorBox.classList.remove('hidden');
        })
        .finally(() => {
            calcBtn.disabled = false;
            calcBtn.innerHTML = '<i class="fas fa-calculator mr-2"></i> Calculate';
        });
    });
});
</script>
@endpush
@endsection