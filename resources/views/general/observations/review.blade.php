@extends('layouts.app')

@section('title', 'Review Observation - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Review Observation</h1>
        <p class="text-sm text-gray-600">{{ $observation->title }}</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <a href="{{ route('observations.show', $observation) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Summary -->
    <div class="bg-blue-50 rounded-xl border border-blue-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm font-medium text-blue-800">Title</p>
                <p class="text-sm text-blue-900">{{ $observation->title }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-blue-800">Reported By</p>
                <p class="text-sm text-blue-900">{{ $observation->reportedBy->name ?? 'Unknown' }} on {{ $observation->created_at->format('M d, Y H:i') }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-blue-800">Category</p>
                <p class="text-sm text-blue-900">{{ $observation->category?->name ?? $observation->other_category ?? 'Not specified' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-blue-800">Priority</p>
                <p class="text-sm text-blue-900">{!! priority_badge($observation->priority) !!}</p>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-blue-200">
            <p class="text-sm font-medium text-blue-800">Description</p>
            <p class="text-sm text-blue-900 mt-1">{{ Str::limit($observation->description, 200) }}</p>
        </div>
    </div>

    <!-- Review Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('observations.review', $observation) }}" class="space-y-6">
            @csrf

            <div>
                <label for="action" class="block text-sm font-medium text-gray-700">Select Action *</label>
                <select name="action" id="action" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" onchange="toggleFields()">
                    <option value="">-- Choose an action --</option>
                    <option value="review">Mark as Reviewed</option>
                    <option value="resolve">Mark as Resolved</option>
                    <option value="close">Close Report</option>
                </select>
                @error('action') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-500">Review: Acknowledge report • Resolve: Issue addressed • Close: Close without resolution</p>
            </div>

            <div>
                <label for="admin_response" class="block text-sm font-medium text-gray-700">Admin Response</label>
                <textarea name="admin_response" id="admin_response" rows="4" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Enter your response...">{{ old('admin_response') }}</textarea>
            </div>

            <div id="actionsTakenGroup" style="display: none;">
                <label for="actions_taken" class="block text-sm font-medium text-gray-700">Actions Taken *</label>
                <textarea name="actions_taken" id="actions_taken" rows="4" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Describe what actions were taken...">{{ old('actions_taken') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Required when marking as resolved. Describe specific steps taken.</p>
            </div>

            <div id="followUpGroup" style="display: none;">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-center">
                        <input type="checkbox" name="requires_follow_up" id="requires_follow_up" value="1" {{ old('requires_follow_up') ? 'checked' : '' }} class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="requires_follow_up" class="ml-2 block text-sm text-gray-900">Requires Follow-up</label>
                    </div>
                    <div>
                        <label for="follow_up_date" class="block text-sm font-medium text-gray-700">Follow-up Date</label>
                        <input type="date" name="follow_up_date" id="follow_up_date" value="{{ old('follow_up_date') }}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" min="{{ now()->toDateString() }}">
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('observations.show', $observation) }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">Save Review</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function toggleFields() {
    const action = document.getElementById('action').value;
    const actionsGroup = document.getElementById('actionsTakenGroup');
    const followUpGroup = document.getElementById('followUpGroup');

    if (action === 'resolve') {
        actionsGroup.style.display = 'block';
        followUpGroup.style.display = 'block';
    } else {
        actionsGroup.style.display = 'none';
        followUpGroup.style.display = 'none';
    }
}
document.addEventListener('DOMContentLoaded', toggleFields);
</script>
@endpush