<?php

namespace App\Services\Poultry;

use App\Models\BatchStateMigration;
use App\Models\Poultry\Batch;
use App\Models\SystemVariable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BatchRecalculationService
{
    public static function recalculateAll(Batch $batch): void
    {
        if (! $batch->exists) {
            return;
        }

        DB::transaction(function () use ($batch) {
            $startingFlock = (int) $batch->starting_flock;
            $initialCost   = (float) $batch->initial_chicken_cost;

            // ───────── Flock totals ─────────
            $flockTotals = $batch->flockRecords()
                ->selectRaw('
                    COALESCE(SUM(mortality), 0) as total_mortality,
                    COALESCE(SUM(culls), 0)     as total_culls,
                    COALESCE(SUM(slaughter), 0) as total_slaughter
                ')
                ->first();

            $totalMortality = (int) $flockTotals->total_mortality;
            $totalCulls     = (int) $flockTotals->total_culls;
            $totalSlaughter = (int) $flockTotals->total_slaughter;

            // ───────── Feed / expenses ─────────
            $totalFeedUsed = (float) $batch->feedRecords()->sum('feed_used');
            $totalFeedCost = (float) $batch->feedRecords()->sum('total_feed_cost');
            $totalExpenses = (float) $batch->expenses()->sum('amount');

            $totalInventoryCost = (float) $batch->inventoryConsumptions()
                ->whereNotIn('source_type', ['waste', 'feed'])
                ->sum('total_cost');

            // ───────── Transfers ─────────
            $transfersOut = BatchStateMigration::where('source_batch_id', $batch->id)
                ->where('migration_type', 'transfer_out')
                ->get();

            $transfersIn = BatchStateMigration::where('destination_batch_id', $batch->id)
                ->where('migration_type', 'transfer_in')
                ->get();

            $transferOutCount      = (int)   $transfersOut->sum('count_moved');        // negative
            $transferOutCost       = (float) $transfersOut->sum('cost_moved');         // negative
            $transferOutMortality  = (float) $transfersOut->sum('mortality_moved');    // negative
            $transferOutFeed       = (float) $transfersOut->sum('feed_moved');         // negative
            $transferOutWeightGain = (float) $transfersOut->sum('weight_gain_moved');  // negative

            $transferInCount       = (int)   $transfersIn->sum('count_moved');         // positive
            $transferInCost        = (float) $transfersIn->sum('cost_moved');          // positive
            $transferInMortality   = (float) $transfersIn->sum('mortality_moved');     // positive
            $transferInFeed        = (float) $transfersIn->sum('feed_moved');          // positive
            $transferInWeightGain  = (float) $transfersIn->sum('weight_gain_moved');   // positive

            // ───────── Current count ─────────
            $currentCount = max(0,
                $startingFlock
                - $totalMortality
                - $totalCulls
                - $totalSlaughter
                + $transferOutCount
            );

            // ───────── Current cost ─────────
            $currentCost = max(0,
                $initialCost
                + $totalFeedCost
                + $totalExpenses
                + $totalInventoryCost
                + $transferOutCost
                + $transferInCost
            );

            // ───────── Current weight ─────────
            [$currentWeight, $currentAvgWeight] = self::calculateCurrentWeight(
                $batch,
                $currentCount,
                $transferInCount
            );

            // ───────── Mortality split ─────────
            $penMortality = $totalMortality;
            $historicalMortality = max(0,
                $totalMortality
                + $transferInMortality
                + $transferOutMortality
            );

            // ───────── Cumulative feed ─────────
            $cumulativeFeed = max(0, $totalFeedUsed + $transferInFeed + $transferOutFeed);

            // ───────── Cumulative weight gain ─────────
            // Three components:
            //   1. Own weight records (integrated ADG × flock × days)
            //   2. Transfer-in contribution (weight arrived with birds)
            //   3. Transfer-out contribution (weight left with birds, negative)
            //
            // All three must be summed. If any is dropped, FCR on the source
            // and destination of a transfer will diverge from reality: the
            // source looks artificially efficient, the destination artificially
            // inefficient.
            $ownWeightGain = BatchCalculationService::calculateTotalWeightGain($batch);
            $cumulativeWeightGain = max(0,
                $ownWeightGain
                + $transferInWeightGain
                + $transferOutWeightGain
            );

            // ───────── Unallocated basis ─────────
            $unallocatedCost = max(0, $currentCost - (float) $batch->cost_allocated_so_far);

            // ───────── Persist ─────────
            $batch->current_count          = $currentCount;
            $batch->current_weight_kg      = $currentWeight;
            $batch->current_cost           = $currentCost;
            $batch->current_average_weight = $currentAvgWeight;

            $batch->current_average_cost = $currentCount > 0
                ? $unallocatedCost / $currentCount
                : 0;

            $batch->total_mortality      = $historicalMortality;
            $batch->historical_mortality = $historicalMortality;
            $batch->pen_mortality        = $penMortality;

            $batch->total_culls       = $totalCulls;
            $batch->total_slaughter   = $totalSlaughter;
            $batch->total_feed_used   = $cumulativeFeed;
            $batch->total_expenses    = $totalExpenses;
            $batch->remaining_flock   = $currentCount;
            $batch->total_weight_gain = $cumulativeWeightGain;

            $batch->mortality_rate = $startingFlock > 0
                ? ($historicalMortality / $startingFlock) * 100
                : 0;

            $batch->save();

            $batch->updateCachedMetrics();
        });
    }

    private static function calculateCurrentWeight(
        Batch $batch,
        int $currentCount,
        int $transferInTotalCount
    ): array {
        if ($currentCount <= 0) {
            return [0.0, 0.0];
        }

        $weightRecords = $batch->weightRecords()
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        if ($weightRecords->isEmpty()) {
            $chickWeight = (float) SystemVariable::getValue('chick_weight_kg', 0.045);
            return [round($currentCount * $chickWeight, 3), round($chickWeight, 3)];
        }

        $latestWeight  = $weightRecords->last();
        $referenceAvg  = (float) $latestWeight->average_weight;
        $referenceDate = $latestWeight->date;

        $initialFlock = $batch->starting_flock - $transferInTotalCount;

        $flockUpToRef = $batch->flockRecords()
            ->whereDate('date', '<=', $referenceDate)
            ->selectRaw('
                COALESCE(SUM(mortality), 0) as total_mortality,
                COALESCE(SUM(culls), 0)     as total_culls,
                COALESCE(SUM(slaughter), 0) as total_slaughter
            ')
            ->first();

        $transfersOutUpToRef = (int) BatchStateMigration::where('source_batch_id', $batch->id)
            ->where('migration_type', 'transfer_out')
            ->whereDate('created_at', '<=', $referenceDate)
            ->sum('count_moved');

        $transfersInUpToRef = (int) BatchStateMigration::where('destination_batch_id', $batch->id)
            ->where('migration_type', 'transfer_in')
            ->whereDate('created_at', '<=', $referenceDate)
            ->sum('count_moved');

        $countAtRef = max(0,
            (int) $initialFlock
            + $transfersInUpToRef
            + $transfersOutUpToRef
            - (int) $flockUpToRef->total_mortality
            - (int) $flockUpToRef->total_culls
            - (int) $flockUpToRef->total_slaughter
        );

        $runningCount  = $countAtRef;
        $runningWeight = $countAtRef * $referenceAvg;

        // Collect events after the reference date.
        $events = collect();

        foreach ($batch->flockRecords()
            ->whereDate('date', '>', $referenceDate)
            ->orderBy('date')
            ->orderBy('id')
            ->get() as $fr) {
            $events->push([
                'kind'   => 'flock',
                'at'     => $fr->date->toDateString() . ' ' . ($fr->created_at?->format('H:i:s') ?? '23:59:59'),
                'record' => $fr,
            ]);
        }

        foreach (BatchStateMigration::where('source_batch_id', $batch->id)
            ->where('migration_type', 'transfer_out')
            ->whereDate('created_at', '>', $referenceDate)
            ->get() as $t) {
            $events->push([
                'kind'   => 'transfer_out',
                'at'     => $t->created_at->toDateTimeString(),
                'record' => $t,
            ]);
        }

        foreach (BatchStateMigration::where('destination_batch_id', $batch->id)
            ->where('migration_type', 'transfer_in')
            ->whereDate('created_at', '>', $referenceDate)
            ->get() as $t) {
            $events->push([
                'kind'   => 'transfer_in',
                'at'     => $t->created_at->toDateTimeString(),
                'record' => $t,
            ]);
        }

        $events = $events->sortBy('at')->values();

        foreach ($events as $event) {
            $avg = $runningCount > 0 ? $runningWeight / $runningCount : 0;

            switch ($event['kind']) {
                case 'flock':
                    $fr = $event['record'];

                    $mortality = (int) $fr->mortality;
                    $runningCount  -= $mortality;
                    $runningWeight -= $mortality * $avg;

                    $culls = (int) $fr->culls;
                    $runningCount  -= $culls;
                    $runningWeight -= $culls * $avg;

                    $slaughter    = (int) $fr->slaughter;
                    $slaughterAvg = ($fr->slaughter_avg_weight && $fr->slaughter_avg_weight > 0)
                        ? (float) $fr->slaughter_avg_weight
                        : $avg;
                    $runningCount  -= $slaughter;
                    $runningWeight -= $slaughter * $slaughterAvg;
                    break;

                case 'transfer_out':
                    $t = $event['record'];
                    $runningCount  += (int)   $t->count_moved;
                    $runningWeight += (float) $t->weight_moved;
                    break;

                case 'transfer_in':
                    $t = $event['record'];
                    $runningCount  += (int)   $t->count_moved;
                    $runningWeight += (float) $t->weight_moved;
                    break;
            }

            $runningCount  = max(0, $runningCount);
            $runningWeight = max(0, $runningWeight);
        }

        $avg = $runningCount > 0 ? $runningWeight / $runningCount : 0;

        return [round($runningWeight, 3), round($avg, 3)];
    }

    public static function recalculateAllBatches(): int
    {
        $count = 0;
        Batch::chunk(50, function ($batches) use (&$count) {
            foreach ($batches as $batch) {
                try {
                    self::recalculateAll($batch);
                    $count++;
                } catch (\Throwable $e) {
                    Log::error("Recalc failed for batch {$batch->id}: " . $e->getMessage());
                }
            }
        });
        return $count;
    }
}