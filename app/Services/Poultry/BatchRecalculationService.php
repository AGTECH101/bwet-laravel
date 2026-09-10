<?php

namespace App\Services\Poultry;

use App\Models\BatchStateMigration;
use App\Models\Poultry\Batch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BatchRecalculationService
{
    /**
     * Fully recalculate a batch's state from raw records + transfer history.
     *
     * Mortality split logic:
     *   - pen_mortality = SUM(flock_records.mortality) – physical deaths in this batch
     *   - historical_mortality = pen_mortality + transfer_in_mortality - transfer_out_mortality
     *   - total_mortality = historical_mortality (carried value)
     */
    public static function recalculateAll(Batch $batch): void
    {
        if (!$batch->exists) {
            return;
        }

        DB::transaction(function () use ($batch) {
            $startingFlock = (int) $batch->starting_flock;
            $initialCost = (float) $batch->initial_chicken_cost;

            // ============ Flock records ============
            $flockTotals = $batch->flockRecords()
                ->selectRaw('
                    COALESCE(SUM(mortality), 0) as total_mortality,
                    COALESCE(SUM(culls), 0) as total_culls,
                    COALESCE(SUM(slaughter), 0) as total_slaughter
                ')
                ->first();

            $totalMortality = (int) $flockTotals->total_mortality;
            $totalCulls = (int) $flockTotals->total_culls;
            $totalSlaughter = (int) $flockTotals->total_slaughter;

            // ============ Feed ============
            $totalFeedUsed = (float) $batch->feedRecords()->sum('feed_used');
            $totalFeedCost = (float) $batch->feedRecords()->sum('total_feed_cost');

            // ============ Expenses ============
            $totalExpenses = (float) $batch->expenses()->sum('amount');

            // ============ Inventory consumptions (excluding waste) ============
            $totalInventoryCost = (float) $batch->inventoryConsumptions()
                ->where('source_type', '!=', 'waste')
                ->sum('total_cost');

            // ============ Transfers OUT ============
            $transfersOut = BatchStateMigration::where('source_batch_id', $batch->id)
                ->where('migration_type', 'transfer_out')
                ->get();

            $transferOutCount = (int) $transfersOut->sum('count_moved');
            $transferOutCost = (float) $transfersOut->sum('cost_moved');
            $transferOutWeight = (float) $transfersOut->sum('weight_moved');
            $transferOutMortality = (float) $transfersOut->sum('mortality_moved');
            $transferOutFeed = (float) $transfersOut->sum('feed_moved');
            $transferOutWeightGain = (float) $transfersOut->sum('weight_gain_moved');

            // ============ Transfers IN ============
            $transfersIn = BatchStateMigration::where('destination_batch_id', $batch->id)
                ->where('migration_type', 'transfer_in')
                ->get();

            $transferInCount = (int) $transfersIn->sum('count_moved');
            $transferInCost = (float) $transfersIn->sum('cost_moved');
            $transferInWeight = (float) $transfersIn->sum('weight_moved');
            $transferInMortality = (float) $transfersIn->sum('mortality_moved');
            $transferInFeed = (float) $transfersIn->sum('feed_moved');
            $transferInWeightGain = (float) $transfersIn->sum('weight_gain_moved');

            // ============ Current count ============
            // starting_flock already accounts for transferred-in birds (incremented during transfer)
            $currentCount = $startingFlock
                - $totalMortality
                - $totalCulls
                - $totalSlaughter
                + $transferOutCount; // negative value

            $currentCount = max(0, $currentCount);

            // ============ Current cost ============
            $currentCost = $initialCost
                + $totalFeedCost
                + $totalExpenses
                + $totalInventoryCost
                + $transferOutCost   // negative
                + $transferInCost;   // positive

            $currentCost = max(0, $currentCost);

            // ============ Weight ============
            $latestWeight = $batch->weightRecords()->latest('date')->first();
            $avgWeight = $latestWeight ? (float) $latestWeight->average_weight : 0;
            $currentWeight = $currentCount * $avgWeight;

            // ============ Mortality split ============
            $penMortality = $totalMortality;
            $historicalMortality = $totalMortality
                + $transferOutMortality
                + $transferInMortality;
            $historicalMortality = max(0, $historicalMortality);

            // ============ Total feed used (cumulative, adjusted by transfers) ============
            $cumulativeFeed = $totalFeedUsed
                + $transferOutFeed   // negative
                + $transferInFeed;   // positive
            $cumulativeFeed = max(0, $cumulativeFeed);

            // ============ Total weight gain (cumulative, adjusted by transfers) ============
            $cumulativeWeightGain = $transferOutWeightGain + $transferInWeightGain;
            $cumulativeWeightGain = max(0, $cumulativeWeightGain);

            // ============ Save ============
            $batch->current_count = $currentCount;
            $batch->current_weight_kg = $currentWeight;
            $batch->current_cost = $currentCost;
            $batch->current_average_weight = $currentCount > 0 ? $currentWeight / $currentCount : 0;
            $batch->current_average_cost = $currentCount > 0 ? $currentCost / $currentCount : 0;

            $batch->total_mortality = $historicalMortality;
            $batch->historical_mortality = $historicalMortality;
            $batch->pen_mortality = $penMortality;

            $batch->total_culls = $totalCulls;
            $batch->total_slaughter = $totalSlaughter;
            $batch->total_feed_used = $cumulativeFeed;
            $batch->total_expenses = $totalExpenses;
            $batch->remaining_flock = $currentCount;
            $batch->total_weight_gain = $cumulativeWeightGain;

            $batch->mortality_rate = $startingFlock > 0
                ? ($historicalMortality / $startingFlock) * 100
                : 0;

            $batch->save();

            // Refresh FCR, profit, etc.
            $batch->updateCachedMetrics();
        });
    }

    /**
     * Recalculate ALL batches.
     *
     * @return int Number of batches processed
     */
    public static function recalculateAllBatches(): int
    {
        $count = 0;
        Batch::chunk(50, function ($batches) use (&$count) {
            foreach ($batches as $batch) {
                try {
                    self::recalculateAll($batch);
                    $count++;
                } catch (\Exception $e) {
                    Log::error("Recalc failed for batch {$batch->id}: " . $e->getMessage());
                }
            }
        });
        return $count;
    }
}