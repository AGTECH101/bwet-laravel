<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\BatchStateMigration;
use App\Models\Poultry\Batch;
use App\Services\Poultry\BatchRecalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BatchTransferAdminController extends Controller
{
    /**
     * Show the edit form for a batch transfer.
     *
     * The route binds the `transfer_out` row. Its paired `transfer_in` row
     * is discovered via the `source_id` link (falling back to a
     * source/destination + timestamp heuristic for legacy rows created
     * before the link was introduced).
     */
    public function edit(BatchStateMigration $transfer)
    {
        if ($transfer->migration_type !== 'transfer_out') {
            abort(404, 'Only transfer_out records can be edited.');
        }

        $transferIn = $this->findPairedTransferIn($transfer);
        $source = $transfer->sourceBatch;
        $destination = $transfer->destinationBatch;

        if (! $source || ! $destination) {
            abort(404, 'Source or destination batch no longer exists.');
        }

        return view('general.history.transfer-edit', compact(
            'transfer',
            'transferIn',
            'source',
            'destination'
        ));
    }

    /**
     * Update a batch transfer in place.
     *
     * Safe-update strategy:
     *   1. Compute the delta between the old and new bird counts.
     *   2. Update both migration rows (out + in) with the new signed values.
     *   3. Adjust destination `starting_flock` by the delta so the
     *      batch's mortality denominator stays correct.
     *   4. Recalculate BOTH batches from raw records so every downstream
     *      metric (weight, cost, FCR, mortality %, profit) reflects the
     *      edited transfer.
     *
     * Only the bird count and per-bird weight are editable. The source and
     * destination batches are fixed — changing them would silently orphan
     * migration rows and corrupt the source's and destination's recalcs.
     */
    public function update(Request $request, BatchStateMigration $transfer)
    {
        if ($transfer->migration_type !== 'transfer_out') {
            abort(404, 'Only transfer_out records can be edited.');
        }

        $transferIn = $this->findPairedTransferIn($transfer);
        if (! $transferIn) {
            return back()->withErrors([
                'transfer' => 'The paired transfer_in record could not be found. This transfer cannot be safely edited.',
            ]);
        }

        $validated = $request->validate([
            'birds_to_transfer' => ['required', 'integer', 'min:1'],
            'manual_weight' => ['required', 'numeric', 'min:0.001', 'max:20'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $source = Batch::findOrFail($transfer->source_batch_id);
        $destination = Batch::findOrFail($transfer->destination_batch_id);

        $oldCount = abs((int) $transfer->count_moved);
        $newCount = (int) $validated['birds_to_transfer'];
        $manualWeight = (float) $validated['manual_weight'];

        // Maximum available birds: current count plus the birds this
        // transfer previously removed from the source (because we're
        // about to reverse that removal and reapply the new amount).
        $maxAvailable = (int) $source->current_count + $oldCount;
        if ($newCount > $maxAvailable) {
            return back()->withInput()->withErrors([
                'birds_to_transfer' => sprintf(
                    'Cannot transfer more than %s birds (source has %s now, plus %s from this transfer).',
                    number_format($maxAvailable),
                    number_format($source->current_count),
                    number_format($oldCount)
                ),
            ]);
        }

        try {
            DB::transaction(function () use (
                $transfer,
                $transferIn,
                $source,
                $destination,
                $oldCount,
                $newCount,
                $manualWeight,
                $validated
            ) {
                // ── Recompute derived values from current source state ──
                $transferWeight = $newCount * $manualWeight;

                $sourceAvgCost = (float) $source->current_average_cost;
                $transferCost = $newCount * $sourceAvgCost;

                $sourceHistoricalMortalityRate = $source->starting_flock > 0
                    ? $source->historical_mortality / $source->starting_flock
                    : 0;
                $transferMortality = $newCount * $sourceHistoricalMortalityRate;

                // Fraction of the pre-transfer source count that is moving.
                // Pre-transfer count = current + old (since the old transfer
                // is about to be reversed).
                $preTransferCount = max(1, (int) $source->current_count + $oldCount);
                $transferFraction = $newCount / $preTransferCount;

                $transferFeed = $source->total_feed_used * $transferFraction;
                $transferWeightGain = $source->total_weight_gain * $transferFraction;

                $reason = $validated['reason'] ?? $transfer->reason;

                // ── Update the migration rows in place, preserving audit trail ──
                $transfer->update([
                    'count_moved' => -$newCount,
                    'weight_moved' => -$transferWeight,
                    'cost_moved' => -$transferCost,
                    'mortality_moved' => -$transferMortality,
                    'feed_moved' => -$transferFeed,
                    'weight_gain_moved' => -$transferWeightGain,
                    'reason' => $reason,
                ]);

                $transferIn->update([
                    'count_moved' => $newCount,
                    'weight_moved' => $transferWeight,
                    'cost_moved' => $transferCost,
                    'mortality_moved' => $transferMortality,
                    'feed_moved' => $transferFeed,
                    'weight_gain_moved' => $transferWeightGain,
                    'reason' => $reason,
                ]);

                // ── Adjust destination starting_flock by the delta ──
                $delta = $newCount - $oldCount;
                if ($delta !== 0) {
                    $destination->starting_flock = max(0, (int) $destination->starting_flock + $delta);
                    $destination->save();
                }

                // ── Full recalc of both batches from raw records + migrations ──
                BatchRecalculationService::recalculateAll($source);
                BatchRecalculationService::recalculateAll($destination);
            });
        } catch (\Throwable $e) {
            Log::error('Batch transfer edit failed: ' . $e->getMessage(), [
                'transfer_id' => $transfer->id,
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()->withErrors([
                'transfer' => 'Could not update the transfer: ' . $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('poultry.batches.show', $source)
            ->with('success', sprintf(
                'Transfer updated: %s birds now move from %s to %s. Both batches have been recalculated.',
                number_format($newCount),
                $source->batch_id,
                $destination->batch_id
            ));
    }

    /**
     * Locate the transfer_in row that belongs to a transfer_out row.
     *
     * Primary lookup: the `source_id` link written by BatchTransferController@store.
     * Fallback: source/destination IDs plus nearest timestamp — used for
     * legacy rows created before the link existed.
     */
    private function findPairedTransferIn(BatchStateMigration $transferOut): ?BatchStateMigration
    {
        $linked = BatchStateMigration::where('migration_type', 'transfer_in')
            ->where('source_id', $transferOut->id)
            ->first();

        if ($linked) {
            return $linked;
        }

        if (! $transferOut->created_at) {
            return null;
        }

        $from = $transferOut->created_at->copy()->subSeconds(30);
        $to = $transferOut->created_at->copy()->addSeconds(30);

        $candidates = BatchStateMigration::where('migration_type', 'transfer_in')
            ->where('source_batch_id', $transferOut->source_batch_id)
            ->where('destination_batch_id', $transferOut->destination_batch_id)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates->sortBy(function ($candidate) use ($transferOut) {
            return abs($candidate->created_at->diffInSeconds($transferOut->created_at));
        })->first();
    }
}