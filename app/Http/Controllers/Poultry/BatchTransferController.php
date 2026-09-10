<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Models\BatchStateMigration;
use App\Models\Poultry\Batch;
use App\Services\Poultry\BatchRecalculationService;
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
            'manual_weight' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $source = Batch::findOrFail($validated['from_batch']);
        $destination = Batch::findOrFail($validated['to_batch']);

        $transferCount = (int) $validated['birds_to_transfer'];
        $manualWeight = (float) $validated['manual_weight'];

        if ($transferCount > $source->current_count) {
            return back()->withInput()->withErrors([
                'birds_to_transfer' => 'Cannot transfer more than the current count (' . $source->current_count . ' birds).',
            ]);
        }

        if ($source->status !== 'active' || $destination->status !== 'active') {
            return back()->withInput()->withErrors([
                'from_batch' => 'Both batches must be active to perform a transfer.',
            ]);
        }

        // ── Calculate transfer metrics ──
        $transferWeight = $transferCount * $manualWeight;

        $sourceAvgCost = (float) $source->current_average_cost;
        $transferCost = $transferCount * $sourceAvgCost;

        // Mortality share (based on historical mortality rate)
        $sourceHistoricalMortalityRate = $source->starting_flock > 0
            ? $source->historical_mortality / $source->starting_flock
            : 0;
        $transferMortality = $transferCount * $sourceHistoricalMortalityRate;

        // Feed and weight gain shares (proportional to fraction of current population)
        $transferFraction = $source->current_count > 0
            ? $transferCount / $source->current_count
            : 0;
        $transferFeed = $source->total_feed_used * $transferFraction;
        $transferWeightGain = $source->total_weight_gain * $transferFraction;

        DB::transaction(function () use (
            $source, $destination,
            $transferCount, $transferWeight, $transferCost,
            $transferMortality, $transferFeed, $transferWeightGain
        ) {
            // Snapshot states before
            $sourceBefore = $source->getCurrentState();
            $destBefore = $destination->getCurrentState();

            // ── Log transfer migrations (source side) ──
            BatchStateMigration::create([
                'source_batch_id' => $source->id,
                'destination_batch_id' => $destination->id,
                'migration_type' => 'transfer_out',
                'source_type' => 'batch_transfer',
                'count_moved' => -$transferCount,
                'weight_moved' => -$transferWeight,
                'cost_moved' => -$transferCost,
                'mortality_moved' => -$transferMortality,
                'feed_moved' => -$transferFeed,
                'weight_gain_moved' => -$transferWeightGain,
                'source_state_before' => $sourceBefore,
                'destination_state_before' => $destBefore,
                'created_by_id' => auth()->id(),
            ]);

            // ── Log transfer migrations (destination side) ──
            BatchStateMigration::create([
                'source_batch_id' => $source->id,
                'destination_batch_id' => $destination->id,
                'migration_type' => 'transfer_in',
                'source_type' => 'batch_transfer',
                'count_moved' => $transferCount,
                'weight_moved' => $transferWeight,
                'cost_moved' => $transferCost,
                'mortality_moved' => $transferMortality,
                'feed_moved' => $transferFeed,
                'weight_gain_moved' => $transferWeightGain,
                'source_state_before' => $sourceBefore,
                'destination_state_before' => $destBefore,
                'created_by_id' => auth()->id(),
            ]);

            // ── Update destination starting_flock so mortality % stays meaningful ──
            $destination->starting_flock += $transferCount;
            $destination->save();

            // ── Full recalc both batches from raw data + migrations ──
            BatchRecalculationService::recalculateAll($source);
            BatchRecalculationService::recalculateAll($destination);
        });

        return redirect()->route('poultry.forms.hub')
            ->with('success', "Successfully transferred {$transferCount} birds from {$source->batch_id} to {$destination->batch_id}. Metrics recalculated for both batches.");
    }
}