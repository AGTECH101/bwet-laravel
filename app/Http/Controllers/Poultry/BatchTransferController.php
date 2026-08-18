<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Models\Poultry\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

        $sourceAverageWeight = (float) $source->getCurrentAverageWeight();
        $destinationAverageWeight = (float) $destination->getCurrentAverageWeight();

        $sourceCostPerBird = (float) $source->getCostPerBird();
        $destinationCostPerBird = (float) $destination->getCostPerBird();

        $sourceTransferCost = $sourceCostPerBird * $transferCount;
        $destinationTransferCost = $destinationCostPerBird * $transferCount;

        $sourceRemainingBefore = (int) $source->remaining_flock;
        $destinationRemainingBefore = (int) $destination->remaining_flock;

        $source->remaining_flock = max(0, $sourceRemainingBefore - $transferCount);
        $destination->remaining_flock = $destinationRemainingBefore + $transferCount;

        $source->initial_chicken_cost = max(0, (float) $source->initial_chicken_cost - $sourceTransferCost);
        $destination->initial_chicken_cost = (float) $destination->initial_chicken_cost + $sourceTransferCost;

        $source->cost_allocated_so_far = max(0, (float) $source->cost_allocated_so_far - $sourceTransferCost);
        $destination->cost_allocated_so_far = (float) $destination->cost_allocated_so_far + $destinationTransferCost;

        $sourceWeightedWeight = $sourceAverageWeight * max(1, $sourceRemainingBefore);
        $destinationWeightedWeight = $destinationAverageWeight * max(1, $destinationRemainingBefore);
        $totalBirdsAfterTransfer = $source->remaining_flock + $destination->remaining_flock;

        if ($totalBirdsAfterTransfer > 0) {
            $combinedAverageWeight = ($sourceWeightedWeight + $destinationWeightedWeight + ($sourceAverageWeight * $transferCount)) / $totalBirdsAfterTransfer;
            $source->current_average_weight = $source->remaining_flock > 0 ? max(0, ($sourceWeightedWeight - ($sourceAverageWeight * $transferCount)) / $source->remaining_flock) : 0;
            $destination->current_average_weight = $combinedAverageWeight;
        }

        $source->save();
        $destination->save();

        $source->updateCachedMetrics();
        $destination->updateCachedMetrics();

        return redirect()->route('poultry.forms.hub')
            ->with('success', 'Batch transfer completed and the receiving batch was updated with transferred birds, weight, and cost basis.');
    }
}
