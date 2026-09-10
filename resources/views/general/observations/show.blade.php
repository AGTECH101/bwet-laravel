@extends('layouts.app')

@section('title', $observation->title . ' - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $observation->title }}</h1>
        <p class="text-sm text-gray-600">Observation Report Details</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
        <a href="{{ route('observations.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
        @can('review', $observation)
        @if($observation->status === 'pending')
        <a href="{{ route('observations.review.form', $observation) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
            <i class="fas fa-check mr-2"></i> Review
        </a>
        @endif
        @endcan
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Status & Priority -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-500">Status</p>
                <div class="mt-1">{!! observation_status_badge($observation->status) !!}</div>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Priority</p>
                <div class="mt-1">{!! priority_badge($observation->priority) !!}</div>
            </div>
        </div>
    </div>

    <!-- Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Report Information</h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Title</dt>
                    <dd class="text-sm text-gray-900">{{ $observation->title }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Category</dt>
                    <dd class="text-sm text-gray-900">{{ $observation->category?->name ?? $observation->other_category ?? 'Not specified' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Reported By</dt>
                    <dd class="text-sm text-gray-900">{{ $observation->reportedBy->name ?? 'Unknown' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Reported Date</dt>
                    <dd class="text-sm text-gray-900">{{ $observation->created_at->format('F j, Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Review Information</h3>
            <dl class="space-y-3">
                @if($observation->reviewedBy)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Reviewed By</dt>
                    <dd class="text-sm text-gray-900">{{ $observation->reviewedBy->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Reviewed Date</dt>
                    <dd class="text-sm text-gray-900">{{ $observation->reviewed_at?->format('F j, Y H:i') }}</dd>
                </div>
                @endif
                @if($observation->resolvedBy)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Resolved By</dt>
                    <dd class="text-sm text-gray-900">{{ $observation->resolvedBy->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Resolved Date</dt>
                    <dd class="text-sm text-gray-900">{{ $observation->resolved_at?->format('F j, Y H:i') }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    <!-- Description -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Description</h3>
        <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $observation->description }}</div>
    </div>

    <!-- Affected Batches -->
    @if($observation->affected_batch_ids && count($observation->affected_batch_ids) > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Affected Batches</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($observation->affectedBatches as $batch)
            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                <a href="{{ route('poultry.batches.show', $batch) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                    {{ $batch->batch_id }} - {{ $batch->name }}
                </a>
                <div class="text-xs text-gray-500 mt-1">
                    Status: {{ ucfirst($batch->status) }} • Age: {{ $batch->current_age_days }} days
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Admin Response & Actions -->
    @if($observation->admin_response || $observation->actions_taken)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @if($observation->admin_response)
        <div class="bg-blue-50 rounded-xl border border-blue-200 p-6">
            <h3 class="text-sm font-semibold text-blue-900 mb-2">Admin Response</h3>
            <p class="text-sm text-blue-800">{{ $observation->admin_response }}</p>
        </div>
        @endif
        @if($observation->actions_taken)
        <div class="bg-green-50 rounded-xl border border-green-200 p-6">
            <h3 class="text-sm font-semibold text-green-900 mb-2">Actions Taken</h3>
            <p class="text-sm text-green-800">{{ $observation->actions_taken }}</p>
        </div>
        @endif
    </div>
    @endif

    <!-- Timeline -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Timeline</h3>
        <div class="space-y-4">
            <div class="flex items-start">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center">
                    <i class="fas fa-plus text-yellow-600 text-sm"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">Reported</p>
                    <p class="text-xs text-gray-500">{{ $observation->created_at->format('F j, Y H:i') }} by {{ $observation->reportedBy->name ?? 'Unknown' }}</p>
                </div>
            </div>
            @if($observation->reviewed_at)
            <div class="flex items-start">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-eye text-blue-600 text-sm"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">Reviewed</p>
                    <p class="text-xs text-gray-500">{{ $observation->reviewed_at->format('F j, Y H:i') }} by {{ $observation->reviewedBy->name ?? 'Unknown' }}</p>
                </div>
            </div>
            @endif
            @if($observation->resolved_at)
            <div class="flex items-start">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fas fa-check text-green-600 text-sm"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">Resolved</p>
                    <p class="text-xs text-gray-500">{{ $observation->resolved_at->format('F j, Y H:i') }} by {{ $observation->resolvedBy->name ?? 'Unknown' }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection