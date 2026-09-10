@extends('layouts.app')

@section('title', 'Flock Records - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Flock Records</h1>
        <p class="text-sm text-gray-600">Mortality, culls, and slaughter entries for {{ $batch->batch_id ?? 'this batch' }}.</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <a href="{{ route('poultry.batches.show', $batch) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back to Batch
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900">Record History</h3>
        <a href="{{ route('poultry.forms.flock-record.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            <i class="fas fa-plus mr-2"></i> Add Record
        </a>
    </div>

    <div class="p-6">
        @if(($records ?? collect())->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mortality</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Culls</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slaughter</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($records as $record)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $record->date->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $record->mortality }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $record->culls }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $record->slaughter }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $record->recordedBy?->name ?? 'Unknown' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-gray-500">No flock records yet.</p>
            </div>
        @endif
    </div>
</div>
@endsection
