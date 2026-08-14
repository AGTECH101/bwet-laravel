@extends('layouts.app')

@section('title', 'Form Hub - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Form Hub</h1>
        <p class="text-sm text-gray-600">Quick data entry for daily operations</p>
    </div>
</div>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-lg bg-white border border-green-200 flex items-center justify-center">
                <i class="fas fa-edit text-green-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-900">Data Entry Forms</h3>
                <p class="text-sm text-gray-600">Select a form to record daily activities</p>
            </div>
        </div>
    </div>

    @if(isset($activeBatches) && count($activeBatches) > 0)
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Filter by batch:</span>
            <div class="flex items-center space-x-2">
                <a href="{{ route('poultry.forms.hub') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium {{ !request()->has('batch') ? 'bg-green-600 text-white' : 'bg-white text-gray-700 border border-gray-300' }}">All</a>
                @foreach($activeBatches->take(5) as $batch)
                <a href="{{ route('poultry.forms.hub', ['batch' => $batch->batch_id]) }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium {{ request('batch') == $batch->batch_id ? 'bg-green-600 text-white' : 'bg-white text-gray-700 border border-gray-300' }}">
                    {{ $batch->batch_id }}
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <div class="p-6">
        @if(isset($activeBatches) && count($activeBatches) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Flock Record -->
            <a href="{{ route('poultry.forms.flock-record.create', request()->only('batch')) }}" class="group relative bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center"><span class="text-2xl">🐔</span></div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Daily</span>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Flock Record</h4>
                    <p class="text-sm text-gray-600 mb-4">Record mortality, culls, and slaughter</p>
                    <div class="flex items-center text-sm text-green-600"><span>Add record</span><i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i></div>
                </div>
            </a>

            <!-- Weight Record -->
            <a href="{{ route('poultry.forms.weight-record.create', request()->only('batch')) }}" class="group relative bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center"><span class="text-2xl">⚖️</span></div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Critical</span>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Weight Record</h4>
                    <p class="text-sm text-gray-600 mb-4">Record bird weights with CV calculation</p>
                    <div class="flex items-center text-sm text-blue-600"><span>Add record</span><i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i></div>
                </div>
            </a>

            <!-- Feed Record -->
            <a href="{{ route('poultry.forms.feed-record.create', request()->only('batch')) }}" class="group relative bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center"><span class="text-2xl">🍽️</span></div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Daily</span>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Feed Record</h4>
                    <p class="text-sm text-gray-600 mb-4">Record feed consumption and calculate FCR</p>
                    <div class="flex items-center text-sm text-green-600"><span>Add record</span><i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i></div>
                </div>
            </a>

            <!-- Inventory Item -->
            <a href="{{ route('poultry.inventory.create') }}" class="group relative bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-lg bg-yellow-100 flex items-center justify-center"><span class="text-2xl">📦</span></div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">As needed</span>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Inventory Item</h4>
                    <p class="text-sm text-gray-600 mb-4">Add new inventory items to stock</p>
                    <div class="flex items-center text-sm text-yellow-600"><span>Add item</span><i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i></div>
                </div>
            </a>

            <!-- Expense -->
            <a href="{{ route('poultry.forms.expense.create', request()->only('batch')) }}" class="group relative bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center"><span class="text-2xl">💸</span></div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">As needed</span>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Expense Record</h4>
                    <p class="text-sm text-gray-600 mb-4">Record expenses for batch or general operations</p>
                    <div class="flex items-center text-sm text-purple-600"><span>Add expense</span><i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i></div>
                </div>
            </a>

            <!-- Inventory Consumption -->
            <a href="{{ route('poultry.forms.inventory-consumption.create') }}" class="group relative bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-lg bg-indigo-100 flex items-center justify-center"><span class="text-2xl">📋</span></div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">As needed</span>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Inventory Consumption</h4>
                    <p class="text-sm text-gray-600 mb-4">Record usage of inventory items</p>
                    <div class="flex items-center text-sm text-indigo-600"><span>Add consumption</span><i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i></div>
                </div>
            </a>
        </div>
        @else
        <div class="text-center py-12">
            <div class="w-20 h-20 mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-4">
                <i class="fas fa-inbox text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Active Batches</h3>
            <p class="text-gray-600 mb-6">You need to create a batch before you can record data.</p>
            <a href="{{ route('poultry.batches.create') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700">
                <i class="fas fa-plus mr-2"></i> Create Your First Batch
            </a>
        </div>
        @endif
    </div>
</div>
@endsection