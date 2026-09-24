@extends('layouts.app')

@section('title', 'Edit Batch Transfer - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Batch Transfer</h1>
        <p class="text-sm text-gray-600">
            {{ $source->batch_id }} → {{ $destination->batch_id }}
        </p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <a href="{{ url()->previous() }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Error summary -->
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <div class="flex items-start">
            <i class="fas fa-exclamation-triangle text-red-600 mt-0.5 mr-3"></i>
            <div class="text-sm text-red-800">
                <p class="font-semibold mb-1">Please fix the following:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <!-- Warning -->
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-amber-600 mt-0.5 mr-3"></i>
            <div class="text-sm text-amber-800">
                <p class="font-semibold">Editing a transfer rewrites history</p>
                <p class="mt-1">
                    Both the source and destination batch will be fully recalculated from their raw
                    records (feed, weight, flock, expenses). The values below replace the existing
                    transfer amounts. There is no undo, but you can edit again to correct a mistake.
                </p>
            </div>
        </div>
    </div>

    <!-- Current transfer summary -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Current Transfer</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Birds Transferred</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format(abs((int) $transfer->count_moved)) }}</p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Total Weight Moved</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format(abs((float) $transfer->weight_moved), 3) }} <span class="text-sm font-normal text-gray-500">kg</span></p>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Total Cost Moved</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ format_currency(abs((float) $transfer->cost_moved)) }}</p>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Recorded</p>
                <p class="text-gray-900">{{ $transfer->created_at->format('M d, Y H:i') }}</p>
            </div>
            <div>
                <p class="text-gray-500">Recorded By</p>
                <p class="text-gray-900">{{ $transfer->createdBy?->name ?? 'Unknown' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Source → Destination</p>
                <p class="text-gray-900">{{ $source->batch_id }} → {{ $destination->batch_id }}</p>
            </div>
        </div>
    </div>

    <!-- Edit form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Transfer</h3>
        <form method="POST" action="{{ route('admin.transfers.update', $transfer) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="birds_to_transfer" class="block text-sm font-medium text-gray-700">
                    Birds Transferred <span class="text-red-500">*</span>
                </label>
                @php
                    $maxAvailable = (int) $source->current_count + abs((int) $transfer->count_moved);
                @endphp
                <input type="number" min="1" max="{{ $maxAvailable }}" name="birds_to_transfer" id="birds_to_transfer"
                    value="{{ old('birds_to_transfer', abs((int) $transfer->count_moved)) }}"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                <p class="mt-1 text-xs text-gray-500">
                    Source batch currently holds {{ number_format($source->current_count) }} birds.
                    Maximum allowed (including the {{ number_format(abs((int) $transfer->count_moved)) }} birds this transfer previously removed):
                    <strong>{{ number_format($maxAvailable) }}</strong>.
                </p>
            </div>

            <div>
                <label for="manual_weight" class="block text-sm font-medium text-gray-700">
                    Average Weight per Bird (kg) <span class="text-red-500">*</span>
                </label>
                @php
                    $currentCount = abs((int) $transfer->count_moved);
                    $currentWeight = abs((float) $transfer->weight_moved);
                    $currentPerBird = $currentCount > 0 ? $currentWeight / $currentCount : 0;
                @endphp
                <input type="number" step="0.001" min="0.001" max="20" name="manual_weight" id="manual_weight"
                    value="{{ old('manual_weight', number_format($currentPerBird, 3, '.', '')) }}"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" required>
                <p class="mt-1 text-xs text-gray-500">
                    Total weight moved = bird count × this number. Currently
                    {{ number_format($currentPerBird, 3) }} kg/bird.
                </p>
            </div>

            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700">Reason / Notes</label>
                <textarea name="reason" id="reason" rows="3"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                    placeholder="Explain why this transfer is being changed...">{{ old('reason', $transfer->reason) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Stored on the audit trail for future reference.</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-700 border border-gray-200">
                <p class="font-medium mb-1">What will happen when you save:</p>
                <ul class="list-disc list-inside space-y-0.5 text-xs text-gray-600">
                    <li>Both migration rows (transfer_out + transfer_in) are updated in place.</li>
                    <li>Destination <code class="text-xs">starting_flock</code> is adjusted by the bird-count delta.</li>
                    <li>Source batch is recalculated from raw records.</li>
                    <li>Destination batch is recalculated from raw records.</li>
                    <li>Every metric on both batches — count, weight, cost, FCR, mortality %, profit — will refresh.</li>
                </ul>
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ url()->previous() }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                    <i class="fas fa-save mr-2"></i> Save Transfer
                </button>
            </div>
        </form>
    </div>
</div>
@endsection