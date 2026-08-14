@extends('layouts.app')

@section('title', 'Record Expense - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Record Expense</h1>
        <p class="text-sm text-gray-600">Record expenses for batch or general operations</p>
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
        <form method="POST" action="{{ route('poultry.forms.expense.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="poultry_batch_id" class="block text-sm font-medium text-gray-700">Batch (Optional)</label>
                <select name="poultry_batch_id" id="poultry_batch_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="">-- General Expense (No Batch) --</option>
                    @foreach($batches ?? [] as $batch)
                    <option value="{{ $batch->id }}" {{ old('poultry_batch_id') == $batch->id ? 'selected' : '' }}>
                        {{ $batch->batch_id }} - {{ $batch->name }}
                    </option>
                    @endforeach
                </select>
                @error('poultry_batch_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="date" class="block text-sm font-medium text-gray-700">Date <span class="text-red-500">*</span></label>
                <input type="date" name="date" id="date" value="{{ old('date', now()->toDateString()) }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                @error('date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="category" class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                <select name="category" id="category" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                    <option value="">-- Select Category --</option>
                    <option value="medication" {{ old('category') == 'medication' ? 'selected' : '' }}>Medication</option>
                    <option value="vaccination" {{ old('category') == 'vaccination' ? 'selected' : '' }}>Vaccination</option>
                    <option value="labor" {{ old('category') == 'labor' ? 'selected' : '' }}>Labor</option>
                    <option value="utilities" {{ old('category') == 'utilities' ? 'selected' : '' }}>Utilities</option>
                    <option value="maintenance" {{ old('category') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="transport" {{ old('category') == 'transport' ? 'selected' : '' }}>Transport</option>
                    <option value="packaging" {{ old('category') == 'packaging' ? 'selected' : '' }}>Packaging</option>
                    <option value="other" {{ old('category') == 'other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description <span class="text-red-500">*</span></label>
                <input type="text" name="description" id="description" value="{{ old('description') }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Expense description" required>
                @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700">Amount (₦) <span class="text-red-500">*</span></label>
                <input type="number" name="amount" id="amount" value="{{ old('amount') }}" step="0.01" min="0.01" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="0.00" required>
                @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="vendor" class="block text-sm font-medium text-gray-700">Vendor (Optional)</label>
                <input type="text" name="vendor" id="vendor" value="{{ old('vendor') }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Vendor name">
                @error('vendor') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="receipt_number" class="block text-sm font-medium text-gray-700">Receipt Number (Optional)</label>
                <input type="text" name="receipt_number" id="receipt_number" value="{{ old('receipt_number') }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Receipt #">
                @error('receipt_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('poultry.forms.hub') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">Save Expense</button>
            </div>
        </form>
    </div>
</div>
@endsection