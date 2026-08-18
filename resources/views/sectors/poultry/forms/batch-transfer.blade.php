@extends('layouts.app')

@section('title', 'Batch Transfer - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Batch Transfer</h1>
        <p class="text-sm text-gray-600">Move birds from one active batch to another with a recorded note</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <a href="{{ route('poultry.forms.hub') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back to Form Hub
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('poultry.forms.batch-transfer.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="from_batch" class="block text-sm font-medium text-gray-700">Source Batch *</label>
                    <select name="from_batch" id="from_batch" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Select source batch</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ old('from_batch') == $batch->id ? 'selected' : '' }}>
                                {{ $batch->batch_id }} - {{ $batch->name }} ({{ $batch->remaining_flock }} birds)
                            </option>
                        @endforeach
                    </select>
                    @error('from_batch') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="to_batch" class="block text-sm font-medium text-gray-700">Destination Batch *</label>
                    <select name="to_batch" id="to_batch" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Select destination batch</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" {{ old('to_batch') == $batch->id ? 'selected' : '' }}>
                                {{ $batch->batch_id }} - {{ $batch->name }} ({{ $batch->remaining_flock }} birds)
                            </option>
                        @endforeach
                    </select>
                    @error('to_batch') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="birds_to_transfer" class="block text-sm font-medium text-gray-700">Birds to Transfer *</label>
                <input type="number" min="1" name="birds_to_transfer" id="birds_to_transfer" value="{{ old('birds_to_transfer') }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="e.g. 50">
                @error('birds_to_transfer') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700">Transfer Reason</label>
                <textarea name="reason" id="reason" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Explain why the birds are being transferred.">{{ old('reason') }}</textarea>
                @error('reason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('poultry.forms.hub') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">Transfer Birds</button>
            </div>
        </form>
    </div>
</div>
@endsection
