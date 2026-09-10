@extends('layouts.app')

@section('title', 'Edit ' . $variable->name . ' - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit {{ $variable->name }}</h1>
        <p class="text-sm text-gray-600">Quick update for system configuration variable</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <a href="{{ route('system.variables.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $variable->name }}</h3>
                    <p class="text-sm text-gray-600">{{ $variable->description ?: 'No description' }}</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                    {{ $variable->category == 'financial' ? 'bg-green-100 text-green-800' :
                       ($variable->category == 'performance' ? 'bg-blue-100 text-blue-800' :
                       ($variable->category == 'weighing' ? 'bg-yellow-100 text-yellow-800' :
                       'bg-gray-100 text-gray-800')) }}">
                    {{ ucfirst($variable->category) }}
                </span>
            </div>
        </div>

        <div class="p-6">
            <!-- Current Value -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Current Value</p>
                <div class="flex items-center justify-between">
                    <code class="text-lg font-mono text-gray-900">{{ $variable->value }}</code>
                    <span class="text-xs text-gray-500">{{ ucfirst($variable->data_type) }}</span>
                </div>
                <div class="mt-2 text-sm text-gray-600">
                    @if($variable->data_type == 'integer')
                        Interpreted as: <strong>{{ (int) $variable->value }}</strong> (Integer)
                    @elseif($variable->data_type == 'decimal')
                        Interpreted as: <strong>{{ (float) $variable->value }}</strong> (Decimal)
                    @elseif($variable->data_type == 'percentage')
                        Interpreted as: <strong>{{ (float) $variable->value }}%</strong> (Percentage)
                    @elseif($variable->data_type == 'boolean')
                        Interpreted as: <strong>{{ filter_var($variable->value, FILTER_VALIDATE_BOOLEAN) ? 'True' : 'False' }}</strong> (Boolean)
                    @else
                        Interpreted as: <strong>"{{ $variable->value }}"</strong> (String)
                    @endif
                </div>
            </div>

            <!-- Update Form -->
            <form method="POST" action="{{ route('system.variables.update', $variable) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="value" class="block text-sm font-medium text-gray-700 mb-2">
                        New Value <span class="text-xs text-gray-500">({{ ucfirst($variable->data_type) }})</span>
                    </label>
                    <div class="flex space-x-2">
                        <input type="text" name="value" id="value" value="{{ old('value', $variable->value) }}" required
                               class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-lg font-mono"
                               placeholder="Enter new value...">
                        <button type="submit" class="px-6 py-3 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                            <i class="fas fa-check mr-2"></i> Update
                        </button>
                    </div>
                    @error('value') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <div class="mt-2 p-2 bg-gray-50 rounded border border-gray-200">
                        <p class="text-xs text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            @if($variable->data_type == 'integer')
                                Enter a whole number (e.g., 100)
                            @elseif($variable->data_type == 'decimal')
                                Enter a decimal number (e.g., 15.50)
                            @elseif($variable->data_type == 'percentage')
                                Enter a percentage (e.g., 20.0)
                            @elseif($variable->data_type == 'boolean')
                                Enter 'true', 'false', 'yes', 'no', '1', or '0'
                            @else
                                Enter any text value
                            @endif
                        </p>
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">{{ old('description', $variable->description) }}</textarea>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ $variable->is_active ? 'checked' : '' }} class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 text-sm text-gray-700">Active</label>
                </div>
            </form>

            <!-- Metadata -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Internal Key</p>
                        <p class="font-mono text-gray-900">{{ $variable->key }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Last Updated</p>
                        <p class="text-gray-900">{{ $variable->updated_at ? $variable->updated_at->format('M d, Y H:i') : 'Never' }}</p>
                    </div>
                </div>
                @if($variable->updatedBy)
                <div class="mt-2 text-sm text-gray-500">
                    By: {{ $variable->updatedBy->name }}
                </div>
                @endif
            </div>

            <!-- Impact Warning -->
            @if(in_array($variable->category, ['financial', 'performance', 'slaughter']))
            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
                <div class="flex">
                    <div class="flex-shrink-0"><i class="fas fa-exclamation-triangle text-yellow-400"></i></div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">System Impact</h3>
                        <p class="text-sm text-yellow-700">Changing this value will immediately affect system calculations.</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection