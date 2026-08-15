@extends('layouts.app')

@section('title', 'Price Calculator - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Price Calculator</h1>
        <p class="text-sm text-gray-600">Estimate the pricing needed to meet a target margin.</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form id="priceCalculatorForm" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="cost_per_bird" class="block text-sm font-medium text-gray-700">Cost per Bird</label>
                    <input type="number" step="0.01" min="0" id="cost_per_bird" name="cost_per_bird" value="{{ $profitMargin ?? 20 }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label for="target_margin" class="block text-sm font-medium text-gray-700">Target Margin (%)</label>
                    <input type="number" step="0.01" min="0" id="target_margin" name="target_margin" value="{{ $profitMargin ?? 20 }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                </div>
            </div>

            <div>
                <label for="dressed_weight" class="block text-sm font-medium text-gray-700">Dressed Weight (kg)</label>
                <input type="number" step="0.01" min="0.01" id="dressed_weight" name="dressed_weight" value="1.5" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                    <i class="fas fa-calculator mr-2"></i> Calculate
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Suggested Pricing</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="rounded-lg bg-gray-50 p-4">
                <div class="text-gray-500">Suggested Price / kg</div>
                <div id="suggested_price" class="mt-2 text-2xl font-bold text-gray-900">₦0.00</div>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <div class="text-gray-500">Minimum Break-even / kg</div>
                <div id="minimum_price" class="mt-2 text-2xl font-bold text-gray-900">₦0.00</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('priceCalculatorForm');

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(form);
        fetch('{{ route('price-calculator.calculate') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('suggested_price').textContent = '₦' + Number(data.suggested_price_per_kg || 0).toFixed(2);
            document.getElementById('minimum_price').textContent = '₦' + Number(data.minimum_price_per_kg || 0).toFixed(2);
        })
        .catch(() => {
            alert('Unable to calculate price right now.');
        });
    });
});
</script>
@endpush
@endsection
