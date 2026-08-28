<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Models\BatchStateMigration;
use App\Models\Poultry\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class BatchTransferController extends Controller
{
    public function create(Request $request)
    {
        Gate::authorize('viewAny', Batch::class);

        $batches = Batch::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        $selectedFrom = $request->filled('from_batch')
            ? Batch::where('batch_id', $request->from_batch)->first()
            : null;

        return view('sectors.poultry.forms.batch-transfer', compact('batches', 'selectedFrom'));
    }

    public function store(Request $request)
    {
        Gate::authorize('update', Batch::class);

        $validated = $request->validate([
            'from_batch' => ['required', 'exists:poultry_batches,id'],
            'to_batch' => ['required', 'exists:poultry_batches,id', 'different:from_batch'],
            'birds_to_transfer' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $source = Batch::findOrFail($validated['from_batch']);
        $destination = Batch::findOrFail($validated['to_batch']);

        // Ensure source has enough birds
        $transferCount = (int) $validated['birds_to_transfer'];
        if ($transferCount > $source->current_count) {
            return back()->withInput()->withErrors([
                'birds_to_transfer' => 'Cannot transfer more than the current count (' . $source->current_count . ' birds).',
            ]);
        }

        // Ensure both batches are active (optional)
        if ($source->status !== 'active' || $destination->status !== 'active') {
            return back()->withInput()->withErrors([
                'from_batch' => 'Both batches must be active to perform a transfer.',
            ]);
        }

        // Calculate the amount to transfer
        $transferWeight = $transferCount * $source->current_average_weight;
        $transferCost = $transferCount * $source->current_average_cost;

        // Execute transaction
        DB::transaction(function () use ($source, $destination, $transferCount, $transferWeight, $transferCost) {
            // Source: apply negative changes
            $sourceChanges = [
                'count' => -$transferCount,
                'weight' => -$transferWeight,
                'cost' => -$transferCost,
            ];
            $source->updateState($sourceChanges, 'transfer_out', $destination);

            // Destination: apply positive changes (already handled by updateState's destination logic)
            // But we need to ensure the destination receives the birds. The updateState method
            // automatically applies the inverse to the destination if provided.
            // However, updateState also logs a 'transfer_in' for the destination.
            // So we don't need to call anything else – it's handled inside updateState.

            // Update other cached fields (like total_feed_used etc.) – not needed for checkpoint,
            // but we keep for compatibility.
            $source->updateCachedMetrics();
            $destination->updateCachedMetrics();
        });

        return redirect()->route('poultry.forms.hub')
            ->with('success', "Successfully transferred {$transferCount} birds from {$source->batch_id} to {$destination->batch_id}.");
    }
}