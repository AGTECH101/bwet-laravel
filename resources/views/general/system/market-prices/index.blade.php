@extends('layouts.app')

@section('title', 'Market Prices - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Market Price Management</h1>
        <p class="text-sm text-gray-600">Set and manage market prices for selling batches</p>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Current Prices -->
    @if(isset($currentPrice))
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg shadow p-6 border-2 border-blue-200">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-blue-700"><i class="fas fa-kiwi-bird mr-2"></i>Price per Bird</h3>
                <span class="text-xs bg-blue-200 text-blue-800 px-2 py-1 rounded-full font-medium">Current</span>
            </div>
            <div class="text-3xl font-bold text-blue-900 mb-3">{{ format_currency($currentPrice->price_per_bird) }}</div>
            <div class="text-xs text-blue-600"><i class="fas fa-calendar-alt mr-1"></i> Set on {{ $currentPrice->effective_date->format('d M Y') }}</div>
            @if($currentPrice->setBy)
            <div class="text-xs text-blue-600 mt-1"><i class="fas fa-user mr-1"></i> By {{ $currentPrice->setBy->name }}</div>
            @endif
        </div>

        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg shadow p-6 border-2 border-green-200">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-green-700"><i class="fas fa-balance-scale mr-2"></i>Price per Kg</h3>
                <span class="text-xs bg-green-200 text-green-800 px-2 py-1 rounded-full font-medium">Current</span>
            </div>
            <div class="text-3xl font-bold text-green-900 mb-3">{{ format_currency($currentPrice->price_per_kg) }}</div>
            <div class="text-xs text-green-600"><i class="fas fa-calendar-alt mr-1"></i> Set on {{ $currentPrice->effective_date->format('d M Y') }}</div>
            @if($currentPrice->setBy)
            <div class="text-xs text-green-600 mt-1"><i class="fas fa-user mr-1"></i> By {{ $currentPrice->setBy->name }}</div>
            @endif
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg shadow p-6 border-2 border-purple-200">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-purple-700"><i class="fas fa-box mr-2"></i>Price per Carton</h3>
                <span class="text-xs bg-purple-200 text-purple-800 px-2 py-1 rounded-full font-medium">Current</span>
            </div>
            <div class="text-3xl font-bold text-purple-900 mb-3">{{ format_currency($currentPrice->price_per_carton) }}</div>
            <div class="text-xs text-purple-600"><i class="fas fa-calendar-alt mr-1"></i> Set on {{ $currentPrice->effective_date->format('d M Y') }}</div>
            @if($currentPrice->setBy)
            <div class="text-xs text-purple-600 mt-1"><i class="fas fa-user mr-1"></i> By {{ $currentPrice->setBy->name }}</div>
            @endif
        </div>
    </div>

    @if($currentPrice->notes)
    <div class="bg-amber-50 border-l-4 border-amber-500 rounded-lg p-4">
        <div class="flex items-start">
            <i class="fas fa-sticky-note text-amber-600 mt-1 mr-3"></i>
            <div>
                <h4 class="text-sm font-semibold text-amber-900">Market Notes</h4>
                <p class="text-sm text-amber-800">{{ $currentPrice->notes }}</p>
            </div>
        </div>
    </div>
    @endif
    @else
    <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-lg p-6">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl mr-4"></i>
            <div>
                <h3 class="text-lg font-semibold text-yellow-900">No Market Prices Set</h3>
                <p class="text-yellow-800">You haven't set any market prices yet. Add the first price entry below.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Add/Update Form -->
    <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">
            <i class="fas fa-plus-circle mr-2"></i>{{ isset($currentPrice) ? 'Update' : 'Create' }} Market Price
        </h3>

        <form method="POST" action="{{ route('system.market-prices.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="price_per_bird" class="block text-sm font-medium text-gray-700 mb-2">Price per Bird (₦)</label>
                    <input type="number" name="price_per_bird" id="price_per_bird" step="0.01" min="0" required
                           value="{{ old('price_per_bird', $currentPrice->price_per_bird ?? '') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="e.g., 850.00">
                </div>
                <div>
                    <label for="price_per_kg" class="block text-sm font-medium text-gray-700 mb-2">Price per Kg (₦)</label>
                    <input type="number" name="price_per_kg" id="price_per_kg" step="0.01" min="0" required
                           value="{{ old('price_per_kg', $currentPrice->price_per_kg ?? '') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="e.g., 2500.00">
                </div>
                <div>
                    <label for="price_per_carton" class="block text-sm font-medium text-gray-700 mb-2">Price per Carton (₦)</label>
                    <input type="number" name="price_per_carton" id="price_per_carton" step="0.01" min="0" required
                           value="{{ old('price_per_carton', $currentPrice->price_per_carton ?? '') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           placeholder="e.g., 75000.00">
                </div>
            </div>

            <div>
                <label for="effective_date" class="block text-sm font-medium text-gray-700 mb-2">Effective Date</label>
                <input type="date" name="effective_date" id="effective_date"
                       value="{{ old('effective_date', $currentPrice->effective_date ?? now()->toDateString()) }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Market Notes (Optional)</label>
                <textarea name="notes" id="notes" rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
                          placeholder="e.g., Festival season, high demand expected...">{{ old('notes', $currentPrice->notes ?? '') }}</textarea>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       {{ old('is_active', $currentPrice->is_active ?? true) ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer">
                <label for="is_active" class="ml-2 text-sm text-gray-700">Set as active market price</label>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    {{ isset($currentPrice) ? 'Update' : 'Create' }} Market Price
                </button>
            </div>
        </form>
    </div>

    <!-- Price History -->
    @if(isset($priceHistory) && count($priceHistory) > 0)
    <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6"><i class="fas fa-history mr-2"></i>Price History (Last 30 Days)</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Per Bird</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Per Kg</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Per Carton</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Set By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($priceHistory as $price)
                    <tr class="hover:bg-gray-50 {{ $price->is_active ? 'bg-green-50' : '' }}">
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $price->effective_date->format('d M Y') }}</td>
                        <td class="px-6 py-4 text-sm text-right text-gray-900 font-medium">{{ format_currency($price->price_per_bird) }}</td>
                        <td class="px-6 py-4 text-sm text-right text-gray-900 font-medium">{{ format_currency($price->price_per_kg) }}</td>
                        <td class="px-6 py-4 text-sm text-right text-gray-900 font-medium">{{ format_currency($price->price_per_carton) }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if($price->is_active)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Active
                            </span>
                            @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <i class="fas fa-history mr-1"></i>Archived
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $price->setBy?->name ?? 'System' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Tips -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h4 class="font-semibold text-blue-900 mb-4"><i class="fas fa-lightbulb mr-2"></i>Quick Tips</h4>
        <ul class="space-y-2 text-sm text-blue-800">
            <li><i class="fas fa-check mr-2"></i>Set prices regularly based on market conditions</li>
            <li><i class="fas fa-check mr-2"></i>Use market notes to document reasons for price changes</li>
            <li><i class="fas fa-check mr-2"></i>Active prices are automatically displayed in marketing materials</li>
            <li><i class="fas fa-check mr-2"></i>Price history is maintained automatically for reference</li>
        </ul>
    </div>
</div>
@endsection