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
            'manual_weight' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $source = Batch::findOrFail($validated['from_batch']);
        $destination = Batch::findOrFail($validated['to_batch']);

        $transferCount = (int) $validated['birds_to_transfer'];
        $manualWeight = (float) $validated['manual_weight'];

        // Ensure source has enough birds
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

        // ─── Calculate transfer metrics ────────────────────

        $transferWeight = $transferCount * $manualWeight;
        $transferCost = $transferCount * $source->current_average_cost;

        // Mortality share (based on historical mortality rate)
        $sourceHistoricalMortalityRate = $source->starting_flock > 0
            ? $source->historical_mortality / $source->starting_flock
            : 0;
        $transferMortality = $transferCount * $sourceHistoricalMortalityRate;

        // Feed and weight gain shares
        $transferFraction = $source->current_count > 0
            ? $transferCount / $source->current_count
            : 0;
        $transferFeed = $source->total_feed_used * $transferFraction;
        $transferWeightGain = $source->total_weight_gain * $transferFraction;

        // ─── Execute transaction ────────────────────────────

        DB::transaction(function () use (
            $source, $destination,
            $transferCount, $transferWeight, $transferCost,
            $transferMortality, $transferFeed, $transferWeightGain
        ) {
            // ── Source updates ──
            $sourceBefore = $source->getCurrentState();

            $source->current_count -= $transferCount;
            $source->current_weight_kg -= $transferWeight;
            $source->current_cost -= $transferCost;
            $source->current_average_weight = $source->current_count > 0
                ? $source->current_weight_kg / $source->current_count
                : 0;
            $source->current_average_cost = $source->current_count > 0
                ? $source->current_cost / $source->current_count
                : 0;

            // Mortality: historical and total decrease; pond stays
            $source->total_mortality -= $transferMortality;
            $source->historical_mortality -= $transferMortality;
            // pond_mortality unchanged
            $source->mortality_rate = $source->starting_flock > 0
                ? ($source->total_mortality / $source->starting_flock) * 100
                : 0;

            $source->total_feed_used -= $transferFeed;
            $source->total_weight_gain -= $transferWeightGain;

            $source->remaining_flock = $source->current_count;
            $source->save();

            // ── Destination updates ──
            $destBefore = $destination->getCurrentState();

            $destination->current_count += $transferCount;
            $destination->current_weight_kg += $transferWeight;
            $destination->current_cost += $transferCost;
            $destination->current_average_weight = $destination->current_count > 0
                ? $destination->current_weight_kg / $destination->current_count
                : 0;
            $destination->current_average_cost = $destination->current_count > 0
                ? $destination->current_cost / $destination->current_count
                : 0;

            // Mortality: historical and total increase
            $destination->total_mortality += $transferMortality;
            $destination->historical_mortality += $transferMortality;
            // pond_mortality unchanged
            $destination->mortality_rate = $destination->starting_flock > 0
                ? ($destination->total_mortality / $destination->starting_flock) * 100
                : 0;

            $destination->total_feed_used += $transferFeed;
            $destination->total_weight_gain += $transferWeightGain;

            // Critical: update starting_flock to include transferred birds
            $destination->starting_flock += $transferCount;

            $destination->remaining_flock = $destination->current_count;
            $destination->save();

            // ── Log migrations ──
            BatchStateMigration::create([
                'source_batch_id' => $source->id,
                'destination_batch_id' => $destination->id,
                'migration_type' => 'transfer_out',
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

            BatchStateMigration::create([
                'source_batch_id' => $destination->id,
                'destination_batch_id' => $source->id,
                'migration_type' => 'transfer_in',
                'count_moved' => $transferCount,
                'weight_moved' => $transferWeight,
                'cost_moved' => $transferCost,
                'mortality_moved' => $transferMortality,
                'feed_moved' => $transferFeed,
                'weight_gain_moved' => $transferWeightGain,
                'source_state_before' => $destBefore,
                'destination_state_before' => $sourceBefore,
                'created_by_id' => auth()->id(),
            ]);

            // Update cached metrics (FCR, profit, etc.)
            $source->updateCachedMetrics();
            $destination->updateCachedMetrics();
        });

        return redirect()->route('poultry.forms.hub')
            ->with('success', "Successfully transferred {$transferCount} birds from {$source->batch_id} to {$destination->batch_id}.");
    }
}