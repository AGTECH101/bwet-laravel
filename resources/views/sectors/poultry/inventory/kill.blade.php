@extends('layouts.app')

@section('title', 'Kill Inventory - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-red-700">Kill Inventory Item</h1>
        <p class="text-sm text-gray-600">Deactivate an inventory item</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <a href="{{ route('poultry.inventory.show', $item) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6 p-4 bg-red-50 rounded-lg border border-red-200">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-red-600 text-2xl mr-4"></i>
                <div>
                    <h3 class="text-lg font-semibold text-red-800">Are you sure?</h3>
                    <p class="text-sm text-red-700 mt-1">
                        You are about to <span class="font-bold">kill</span> (deactivate) the inventory item:
                        <span class="font-bold">{{ $item->name }}</span>
                    </p>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        <li>The item will remain in the database for audit purposes</li>
                        <li>It will be hidden from active inventory lists</li>
                        <li>Consumption records will be preserved</li>
                        <li>This action can be reviewed but not undone</li>
                    </ul>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('poultry.inventory.kill', $item) }}" class="space-y-6">
            @csrf

            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700">Reason (Optional)</label>
                <textarea name="reason" id="reason" rows="4" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Explain why you are killing this inventory item...">{{ old('reason') }}</textarea>
                @error('reason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('poultry.inventory.show', $item) }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                    <i class="fas fa-skull-crossbones mr-2"></i> Kill Item
                </button>
            </div>
        </form>
    </div>
</div>
@endsection