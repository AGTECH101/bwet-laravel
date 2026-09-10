@extends('layouts.app')

@section('title', 'Edit Expense - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Expense</h1>
        <p class="text-sm text-gray-600">Update expense details for {{ $expense->batch?->batch_id ?? 'this batch' }}</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('poultry.expenses.update', $expense) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="poultry_batch_id" class="block text-sm font-medium text-gray-700">Batch (Optional)</label>
                <select name="poultry_batch_id" id="poultry_batch_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="">-- General Expense (No Batch) --</option>
                    @foreach(
                        \App\Models\Poultry\Batch::where('status', 'active')->get() ?? [] as $batch
                    )
                        <option value="{{ $batch->id }}" {{ old('poultry_batch_id', $expense->poultry_batch_id) == $batch->id ? 'selected' : '' }}>
                            {{ $batch->batch_id }} - {{ $batch->name }}
                        </option>
                    @endforeach
                </select>
                @error('poultry_batch_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="date" class="block text-sm font-medium text-gray-700">Date <span class="text-red-500">*</span></label>
                <input type="date" name="date" id="date" value="{{ old('date', $expense->date->format('Y-m-d')) }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                @error('date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="category" class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                <select name="category" id="category" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                    <option value="">-- Select Category --</option>
                    <option value="medication" {{ old('category', $expense->category) == 'medication' ? 'selected' : '' }}>Medication</option>
                    <option value="vaccination" {{ old('category', $expense->category) == 'vaccination' ? 'selected' : '' }}>Vaccination</option>
                    <option value="labor" {{ old('category', $expense->category) == 'labor' ? 'selected' : '' }}>Labor</option>
                    <option value="utilities" {{ old('category', $expense->category) == 'utilities' ? 'selected' : '' }}>Utilities</option>
                    <option value="maintenance" {{ old('category', $expense->category) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="transport" {{ old('category', $expense->category) == 'transport' ? 'selected' : '' }}>Transport</option>
                    <option value="packaging" {{ old('category', $expense->category) == 'packaging' ? 'selected' : '' }}>Packaging</option>
                    <option value="other" {{ old('category', $expense->category) == 'other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description <span class="text-red-500">*</span></label>
                <input type="text" name="description" id="description" value="{{ old('description', $expense->description) }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700">Amount (₦) <span class="text-red-500">*</span></label>
                <input type="number" name="amount" id="amount" value="{{ old('amount', $expense->amount) }}" step="0.01" min="0.01" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="vendor" class="block text-sm font-medium text-gray-700">Vendor (Optional)</label>
                <input type="text" name="vendor" id="vendor" value="{{ old('vendor', $expense->vendor) }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Vendor name">
                @error('vendor') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="receipt_number" class="block text-sm font-medium text-gray-700">Receipt Number (Optional)</label>
                <input type="text" name="receipt_number" id="receipt_number" value="{{ old('receipt_number', $expense->receipt_number) }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Receipt #">
                @error('receipt_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('poultry.batches.show', $expense->batch) }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">Update Expense</button>
            </div>
        </form>
    </div>
</div>
@endsection
