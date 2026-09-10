@extends('layouts.app')

@section('title', 'Add Inventory Item - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Add Inventory Item</h1>
        <p class="text-sm text-gray-600">Register new inventory items for stock management</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <a href="{{ route('poultry.inventory.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('poultry.inventory.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Item Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="e.g., Starter Feed" required>
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                    <select name="category" id="category" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                        <option value="">-- Select Category --</option>
                        <option value="feed" {{ old('category') == 'feed' ? 'selected' : '' }}>Feed</option>
                        <option value="vaccine" {{ old('category') == 'vaccine' ? 'selected' : '' }}>Vaccine</option>
                        <option value="medicine" {{ old('category') == 'medicine' ? 'selected' : '' }}>Medicine</option>
                        <option value="consumables" {{ old('category') == 'consumables' ? 'selected' : '' }}>Consumables</option>
                        <option value="packaging" {{ old('category') == 'packaging' ? 'selected' : '' }}>Packaging</option>
                        <option value="other" {{ old('category') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="unit" class="block text-sm font-medium text-gray-700">Unit <span class="text-red-500">*</span></label>
                    <select name="unit" id="unit" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                        <option value="">-- Select Unit --</option>
                        <option value="kg" {{ old('unit') == 'kg' ? 'selected' : '' }}>Kilogram (kg)</option>
                        <option value="g" {{ old('unit') == 'g' ? 'selected' : '' }}>Gram (g)</option>
                        <option value="l" {{ old('unit') == 'l' ? 'selected' : '' }}>Liter (l)</option>
                        <option value="ml" {{ old('unit') == 'ml' ? 'selected' : '' }}>Milliliter (ml)</option>
                        <option value="unit" {{ old('unit') == 'unit' ? 'selected' : '' }}>Unit</option>
                        <option value="bag" {{ old('unit') == 'bag' ? 'selected' : '' }}>Bag</option>
                        <option value="box" {{ old('unit') == 'box' ? 'selected' : '' }}>Box</option>
                    </select>
                    @error('unit') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="quantity_in_stock" class="block text-sm font-medium text-gray-700">Initial Stock <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity_in_stock" id="quantity_in_stock" value="{{ old('quantity_in_stock', 0) }}" step="0.001" min="0" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                    @error('quantity_in_stock') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="minimum_quantity" class="block text-sm font-medium text-gray-700">Minimum Stock Level</label>
                    <input type="number" name="minimum_quantity" id="minimum_quantity" value="{{ old('minimum_quantity', 0) }}" step="0.001" min="0" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    @error('minimum_quantity') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="cost_per_unit" class="block text-sm font-medium text-gray-700">Cost per Unit (₦) <span class="text-red-500">*</span></label>
                <input type="number" name="cost_per_unit" id="cost_per_unit" value="{{ old('cost_per_unit', 0) }}" step="0.01" min="0" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                @error('cost_per_unit') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="vendor" class="block text-sm font-medium text-gray-700">Vendor</label>
                <input type="text" name="vendor" id="vendor" value="{{ old('vendor') }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Vendor name">
                @error('vendor') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                <label for="is_active" class="ml-2 text-sm text-gray-700">Active (available for use)</label>
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('poultry.inventory.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">Add Item</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const unitSelect = document.getElementById('unit');
    const categorySelect = document.getElementById('category');

    function updateUnitOptions() {
        const category = categorySelect.value;
        const units = {
            'feed': ['kg', 'bag'],
            'vaccine': ['unit', 'ml'],
            'medicine': ['kg', 'g', 'ml', 'unit'],
            'consumables': ['unit', 'box'],
            'packaging': ['box', 'unit'],
            'other': ['kg', 'g', 'l', 'ml', 'unit', 'bag', 'box']
        };

        const allowed = units[category] || ['kg', 'g', 'l', 'ml', 'unit', 'bag', 'box'];
        Array.from(unitSelect.options).forEach(option => {
            option.style.display = allowed.includes(option.value) ? '' : 'none';
        });

        if (!allowed.includes(unitSelect.value)) {
            unitSelect.value = allowed[0] || '';
        }
    }

    categorySelect.addEventListener('change', updateUnitOptions);
    updateUnitOptions();

    // Auto-convert bag to kg if feed selected
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const category = categorySelect.value;
        const unit = unitSelect.value;
        if (category === 'feed' && unit === 'bag') {
            const stockInput = document.getElementById('quantity_in_stock');
            const minInput = document.getElementById('minimum_quantity');
            const costInput = document.getElementById('cost_per_unit');
            if (stockInput.value && parseFloat(stockInput.value) > 0) {
                stockInput.value = parseFloat(stockInput.value) * 25;
            }
            if (minInput.value && parseFloat(minInput.value) > 0) {
                minInput.value = parseFloat(minInput.value) * 25;
            }
            if (costInput.value && parseFloat(costInput.value) > 0) {
                costInput.value = parseFloat(costInput.value) / 25;
            }
            unitSelect.value = 'kg';
        }
    });
});
</script>
@endpush
@endsection