@extends('layouts.app')

@section('title', 'Control Panel - ' . config('app.name'))

@section('page_header')
<div class="md:flex md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Control Panel</h1>
        <p class="text-sm text-gray-600">Edit or delete records. All metrics are recalculated automatically.</p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
            <i class="fas fa-shield-alt mr-2"></i> Admin Only
        </span>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Batch Selector -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <label for="batch" class="block text-sm font-medium text-gray-700 mb-1">Select Batch</label>
                <select name="batch" id="batch" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" onchange="this.form.submit()">
                    <option value="">-- Select a Batch --</option>
                    @foreach($allBatches as $batch)
                        <option value="{{ $batch->id }}" {{ $selectedBatch && $selectedBatch->id == $batch->id ? 'selected' : '' }}>
                            {{ $batch->batch_id }} - {{ $batch->name }} ({{ $batch->current_count }} birds)
                        </option>
                    @endforeach
                </select>
            </div>
            @if($selectedBatch)
                <div class="flex items-end gap-2">
                    <a href="{{ route('control-panel.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-times mr-2"></i> Clear
                    </a>
                </div>
            @endif
        </form>
    </div>

    @if($selectedBatch)
        <!-- Batch Header -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center space-x-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">{{ $selectedBatch->batch_id }}</h2>
                        <p class="text-sm text-gray-600">{{ $selectedBatch->name }} · {{ $selectedBatch->status }} · {{ $selectedBatch->age_days }} days</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                        {{ $selectedBatch->current_count }} birds
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="openEditModal('poultry_batches', {{ $selectedBatch->id }})" class="inline-flex items-center px-3 py-1.5 text-sm text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-md">
                        <i class="fas fa-edit mr-1"></i> Edit Batch
                    </button>
                    <button type="button" onclick="openDeleteModal('poultry_batches', {{ $selectedBatch->id }}, '{{ $selectedBatch->batch_id }}')" class="inline-flex items-center px-3 py-1.5 text-sm text-red-700 bg-red-50 hover:bg-red-100 rounded-md">
                        <i class="fas fa-trash mr-1"></i> Delete Batch
                    </button>
                </div>
            </div>

            <!-- Records -->
            <div class="p-6 space-y-6">

                <!-- Flock Records -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                            <i class="fas fa-kiwi-bird mr-1 text-red-500"></i> Flock Records
                        </h3>
                        <span class="text-xs text-gray-500">{{ $selectedBatch->flockRecords->count() }} records</span>
                    </div>
                    @if($selectedBatch->flockRecords->isNotEmpty())
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Mortality</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Culls</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Slaughter</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Notes</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach($selectedBatch->flockRecords as $record)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 whitespace-nowrap">{{ $record->date->format('Y-m-d') }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-red-600 font-medium">{{ $record->mortality }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-yellow-600">{{ $record->culls }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-green-600">{{ $record->slaughter }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-gray-500 max-w-xs truncate">{{ $record->notes ?? '—' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-right">
                                                <button type="button" onclick="openEditModal('flock_records', {{ $record->id }})" class="text-blue-600 hover:text-blue-800 mr-2" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" onclick="openDeleteModal('flock_records', {{ $record->id }}, 'Flock #{{ $record->id }}')" class="text-red-600 hover:text-red-800" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-xs text-gray-400">No flock records.</p>
                    @endif
                </div>

                <!-- Weight Records -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                            <i class="fas fa-weight mr-1 text-blue-500"></i> Weight Records
                        </h3>
                        <span class="text-xs text-gray-500">{{ $selectedBatch->weightRecords->count() }} records</span>
                    </div>
                    @if($selectedBatch->weightRecords->isNotEmpty())
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Birds</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Avg Weight</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">CV</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach($selectedBatch->weightRecords as $record)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 whitespace-nowrap">{{ $record->date->format('Y-m-d') }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">{{ $record->birds_weighed }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap font-medium">{{ number_format($record->average_weight, 3) }} kg</td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                <span class="{{ $record->coefficient_variation >= 15 ? 'text-red-600 font-bold' : ($record->coefficient_variation >= 12 ? 'text-yellow-600' : 'text-green-600') }}">
                                                    {{ number_format($record->coefficient_variation, 2) }}%
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap text-right">
                                                <button type="button" onclick="openEditModal('weight_records', {{ $record->id }})" class="text-blue-600 hover:text-blue-800 mr-2" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" onclick="openDeleteModal('weight_records', {{ $record->id }}, 'Weight #{{ $record->id }}')" class="text-red-600 hover:text-red-800" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-xs text-gray-400">No weight records.</p>
                    @endif
                </div>

                <!-- Feed Records -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                            <i class="fas fa-utensils mr-1 text-green-500"></i> Feed Records
                        </h3>
                        <span class="text-xs text-gray-500">{{ $selectedBatch->feedRecords->count() }} records</span>
                    </div>
                    @if($selectedBatch->feedRecords->isNotEmpty())
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Feed Used</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Cost/kg</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Total Cost</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach($selectedBatch->feedRecords as $record)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 whitespace-nowrap">{{ $record->date->format('Y-m-d') }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap font-medium">{{ number_format($record->feed_used, 2) }} kg</td>
                                            <td class="px-3 py-2 whitespace-nowrap">{{ number_format($record->feed_cost_per_kg, 2) }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap font-medium">{{ number_format($record->total_feed_cost, 2) }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-right">
                                                <button type="button" onclick="openEditModal('feed_records', {{ $record->id }})" class="text-blue-600 hover:text-blue-800 mr-2" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" onclick="openDeleteModal('feed_records', {{ $record->id }}, 'Feed #{{ $record->id }}')" class="text-red-600 hover:text-red-800" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-xs text-gray-400">No feed records.</p>
                    @endif
                </div>

                <!-- Expenses (NEW) -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                            <i class="fas fa-money-bill-wave mr-1 text-purple-500"></i> Expenses
                        </h3>
                        <span class="text-xs text-gray-500">{{ $selectedBatch->expenses->count() }} records</span>
                    </div>
                    @if($selectedBatch->expenses->isNotEmpty())
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Category</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Description</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Amount</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Vendor</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach($selectedBatch->expenses as $expense)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 whitespace-nowrap">{{ $expense->date->format('Y-m-d') }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                <span class="inline-block px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">{{ ucfirst($expense->category) }}</span>
                                            </td>
                                            <td class="px-3 py-2 text-gray-700 max-w-xs truncate">{{ $expense->description }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap font-medium">{{ format_currency($expense->amount) }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-gray-500">{{ $expense->vendor ?? '—' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap text-right">
                                                <button type="button" onclick="openEditModal('expenses', {{ $expense->id }})" class="text-blue-600 hover:text-blue-800 mr-2" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" onclick="openDeleteModal('expenses', {{ $expense->id }}, 'Expense #{{ $expense->id }}')" class="text-red-600 hover:text-red-800" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-xs text-gray-400">No expenses recorded for this batch.</p>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <i class="fas fa-hand-pointer text-gray-300 text-4xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900">Select a Batch</h3>
            <p class="text-gray-500">Choose a batch from the dropdown above to view and manage its records.</p>
        </div>
    @endif
</div>

<!-- ============================================================ -->
<!-- EDIT MODAL -->
<!-- ============================================================ -->
<div id="editModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeEditModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
            <div class="bg-white px-6 pt-6 pb-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Record</h3>
                    <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div id="editLoading" class="text-center py-6">
                    <i class="fas fa-spinner fa-spin text-2xl text-primary-600"></i>
                    <p class="mt-2 text-gray-500">Loading record...</p>
                </div>

                <div id="editFormContent" class="hidden">
                    <p class="text-xs text-gray-500 mb-3">
                        <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                        Grayed-out fields are calculated automatically when you save.
                    </p>
                    <form id="editForm" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="table" id="edit_table">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="batch_id" id="edit_batch_id" value="{{ $selectedBatch?->id ?? '' }}">

                        <div id="editFields" class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-96 overflow-y-auto p-1"></div>

                        <div id="editError" class="hidden text-sm text-red-700 bg-red-50 border border-red-200 p-3 rounded-lg"></div>

                        <div class="pt-4 border-t border-gray-200 flex justify-end space-x-3">
                            <button type="button" onclick="closeEditModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</button>
                            <button type="submit" id="editSubmitBtn" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50">
                                <i class="fas fa-save mr-2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- DELETE MODAL -->
<!-- ============================================================ -->
<div id="deleteModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeDeleteModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-6 pt-6 pb-4">
                <div class="flex items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Delete Record</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Are you sure you want to delete this record? This action cannot be undone.
                        </p>
                        <form id="deleteForm" class="mt-4">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="table" id="delete_table">
                            <input type="hidden" name="id" id="delete_id">
                            <input type="hidden" name="batch_id" id="delete_batch_id" value="{{ $selectedBatch?->id ?? '' }}">
                            <div id="deleteError" class="hidden text-sm text-red-700 bg-red-50 border border-red-200 p-3 rounded-lg mb-4"></div>
                            <div class="flex justify-end space-x-3">
                                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</button>
                                <button type="submit" id="deleteSubmitBtn" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50">
                                    <i class="fas fa-trash mr-2"></i> Delete Permanently
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
const GET_URL = '{{ route("control-panel.get-record") }}';
const UPDATE_URL = '{{ route("control-panel.update-record") }}';
const DELETE_URL = '{{ route("control-panel.delete-record") }}';

// Select options for known enum fields
const SELECT_OPTIONS = {
    category: ['medication', 'vaccination', 'labor', 'utilities', 'maintenance', 'transport', 'packaging', 'other'],
    status: ['active', 'closed', 'completed'],
    phase: ['brooding', 'batch'],
};

// ============================================================
// EDIT MODAL
// ============================================================
function openEditModal(table, id) {
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editLoading').classList.remove('hidden');
    document.getElementById('editFormContent').classList.add('hidden');
    document.getElementById('editError').classList.add('hidden');
    document.getElementById('editFields').innerHTML = '';
    document.getElementById('edit_table').value = table;
    document.getElementById('edit_id').value = id;

    fetch(`${GET_URL}?table=${encodeURIComponent(table)}&id=${encodeURIComponent(id)}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(async (response) => {
        const text = await response.text();
        let data;
        try { data = JSON.parse(text); } catch (e) {
            throw new Error('Server returned an unexpected response. Please refresh and try again.');
        }
        if (!response.ok) throw new Error(data.error || 'Failed to load record.');
        return data;
    })
    .then(data => {
        document.getElementById('editLoading').classList.add('hidden');
        document.getElementById('editFormContent').classList.remove('hidden');

        const record = data.record;
        const primaryKey = data.primaryKey;
        const editableFields = data.editableFields || [];
        const lockedFields = data.lockedFields || [];

        let html = '';
        Object.keys(record).forEach(key => {
            const value = record[key] ?? '';
            const isPrimary = key === primaryKey;
            const isLocked = lockedFields.includes(key);
            const isEditable = editableFields.includes(key);

            if (!isPrimary && !isLocked && !isEditable) return;

            const label = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            const badge = isLocked
                ? '<span class="ml-1 text-xs text-gray-400">(calculated)</span>'
                : (isPrimary ? '<span class="ml-1 text-xs text-gray-400">(ID)</span>' : '');

            let input;
            if (isPrimary || isLocked) {
                input = `<input type="text" value="${String(value).replace(/"/g, '&quot;')}" readonly class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md shadow-sm text-gray-500 cursor-not-allowed text-sm">`;
            } else if (key === 'date' || key === 'start_date') {
                const dateVal = String(value).substring(0, 10);
                input = `<input type="date" id="field_${key}" name="data[${key}]" value="${dateVal}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">`;
            } else if (SELECT_OPTIONS[key]) {
                let opts = SELECT_OPTIONS[key].map(o => `<option value="${o}" ${value === o ? 'selected' : ''}>${o.charAt(0).toUpperCase() + o.slice(1)}</option>`).join('');
                input = `<select id="field_${key}" name="data[${key}]" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">${opts}</select>`;
            } else if (typeof value === 'object' || ['notes'].includes(key)) {
                const displayValue = typeof value === 'object' ? JSON.stringify(value, null, 2) : value;
                input = `<textarea id="field_${key}" name="data[${key}]" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm font-mono">${displayValue}</textarea>`;
            } else {
                input = `<input type="text" id="field_${key}" name="data[${key}]" value="${String(value).replace(/"/g, '&quot;')}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">`;
            }

            html += `
                <div class="col-span-1">
                    <label class="block text-xs font-medium text-gray-700">${label}${badge}</label>
                    ${input}
                </div>
            `;
        });
        document.getElementById('editFields').innerHTML = html;
    })
    .catch(error => {
        document.getElementById('editLoading').classList.add('hidden');
        document.getElementById('editFormContent').classList.remove('hidden');
        const err = document.getElementById('editError');
        err.textContent = error.message;
        err.classList.remove('hidden');
    });
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const submitBtn = document.getElementById('editSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';

    const formData = new FormData(this);
    const data = {};
    let batchId = null;

    for (let [key, value] of formData.entries()) {
        if (key === 'table' || key === 'id' || key === 'batch_id' || key === '_token' || key === '_method') {
            if (key === 'batch_id') batchId = value;
            continue;
        }
        if (key.startsWith('data[')) {
            const fieldName = key.replace('data[', '').replace(']', '');
            data[fieldName] = value;
        }
    }

    const payload = {
        table: formData.get('table'),
        id: formData.get('id'),
        data: data,
        batch_id: batchId
    };

    fetch(UPDATE_URL, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
    })
    .then(async (response) => {
        const text = await response.text();
        let json;
        try { json = JSON.parse(text); } catch (e) {
            throw new Error('Server returned an unexpected response.');
        }
        if (!response.ok) throw new Error(json.error || 'Failed to update.');
        return json;
    })
    .then(json => {
        if (json.success) {
            window.location.href = '{{ route("control-panel.index") }}?batch=' + (json.batch_id || batchId || '');
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i> Save Changes';
        const err = document.getElementById('editError');
        err.textContent = error.message;
        err.classList.remove('hidden');
    });
});

// ============================================================
// DELETE MODAL
// ============================================================
function openDeleteModal(table, id, identifier) {
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('delete_table').value = table;
    document.getElementById('delete_id').value = id;
    document.getElementById('deleteError').classList.add('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

document.getElementById('deleteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const submitBtn = document.getElementById('deleteSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...';

    const formData = new FormData(this);
    const payload = {
        table: formData.get('table'),
        id: formData.get('id'),
        batch_id: formData.get('batch_id')
    };

    fetch(DELETE_URL, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
    })
    .then(async (response) => {
        const text = await response.text();
        let json;
        try { json = JSON.parse(text); } catch (e) {
            throw new Error('Server returned an unexpected response.');
        }
        if (!response.ok) throw new Error(json.error || 'Failed to delete.');
        return json;
    })
    .then(json => {
        if (json.success) {
            window.location.href = '{{ route("control-panel.index") }}?batch=' + (json.batch_id || payload.batch_id || '');
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-trash mr-2"></i> Delete Permanently';
        const err = document.getElementById('deleteError');
        err.textContent = error.message;
        err.classList.remove('hidden');
    });
});
</script>
@endpush
@endsection