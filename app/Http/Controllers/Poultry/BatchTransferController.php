<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Models\Poultry\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class BatchTransferController extends Controller
{
    public function create(Request $request)
    {
        Gate::authorize('viewAny', Batch::class);

        $batches = Batch::where('status', 'active')->orderBy('created_at', 'desc')->get();
        $selectedFrom = $request->filled('from_batch') ? Batch::where('batch_id', $request->from_batch)->first() : null;

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

        $transferCount = (int) $validated['birds_to_transfer'];
        if ($transferCount > (int) $source->remaining_flock) {
            return back()->withInput()->withErrors([
                'birds_to_transfer' => 'Transfer quantity cannot exceed the remaining flock in the source batch.',
            ]);
        }

        // Get current average weights
        $sourceAverageWeight = (float) $source->getCurrentAverageWeight();
        $destinationAverageWeight = (float) $destination->getCurrentAverageWeight();

        // Calculate cost per bird for the source using only initial chicken cost
        // (other costs like feed and expenses stay with the batch)
        $sourceCostPerBird = $source->remaining_flock > 0
            ? (float) $source->initial_chicken_cost / $source->remaining_flock
            : 0;

        $sourceTransferCost = $sourceCostPerBird * $transferCount;

        // --- Update source batch ---
        $source->remaining_flock = max(0, $source->remaining_flock - $transferCount);
        $source->initial_chicken_cost = max(0, $source->initial_chicken_cost - $sourceTransferCost);
        // DO NOT modify cost_allocated_so_far - it tracks slaughter allocations

        // --- Update destination batch ---
        $destination->remaining_flock += $transferCount;
        $destination->initial_chicken_cost += $sourceTransferCost;
        // DO NOT modify cost_allocated_so_far

        // --- Update average weight (if column exists) ---
        if (Schema::hasColumn('poultry_batches', 'current_average_weight')) {
            // Source: if birds remain, average weight stays the same (we assume transferred birds are representative)
            $source->current_average_weight = $source->remaining_flock > 0 ? $sourceAverageWeight : 0;

            // Destination: compute new average weight including transferred birds
            if ($destination->remaining_flock > 0) {
                $totalWeightBefore = ($destination->remaining_flock - $transferCount) * $destinationAverageWeight;
                $totalWeightTransferred = $transferCount * $sourceAverageWeight;
                $destination->current_average_weight = ($totalWeightBefore + $totalWeightTransferred) / $destination->remaining_flock;
            } else {
                $destination->current_average_weight = 0;
            }
        }

        // Save both batches
        $source->save();
        $destination->save();

        // Recalculate all cached metrics for both batches
        $source->updateCachedMetrics();
        $destination->updateCachedMetrics();

        // Create a notification (optional)
        \App\Models\Notification::createGlobal(
            'batch_transfer',
            "Batch Transfer Completed",
            "Transferred {$transferCount} birds from {$source->batch_id} to {$destination->batch_id}",
            auth()->user(),
            $destination
        );

        return redirect()->route('poultry.forms.hub')
            ->with('success', "Successfully transferred {$transferCount} birds from {$source->batch_id} to {$destination->batch_id}.");
    }
}