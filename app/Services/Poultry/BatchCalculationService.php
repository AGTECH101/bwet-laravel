<?php

namespace App\Services\Poultry;

use App\Models\BatchStateMigration;
use App\Models\Poultry\Batch;
use App\Models\SystemVariable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BatchCalculationService
{
    public static function calculateRequiredSampleSize(?int $remainingFlock): int
    {
        $remainingFlock = max(0, (int) ($remainingFlock ?? 0));

        if ($remainingFlock <= 0) {
            return 0;
        }

        $tenPercent = $remainingFlock * 0.1;
        $required = (int) ceil($tenPercent);
        return min(max($required, 5), 10);
    }

    public static function getCurrentAverageWeight(Batch $batch): float
    {
        $lastWeight = $batch->weightRecords()->latest('date')->first();
        if (!$lastWeight) {
            return 0.0;
        }

        $daysSince = (int) $lastWeight->date->diffInDays(Carbon::today(), true);
        $n = (int) SystemVariable::getValue('weighing_frequency_days', 4);

        $weightNDaysAgo = $batch->weightRecords()
            ->where('date', '<=', $lastWeight->date->copy()->subDays($n))
            ->latest('date')
            ->first();

        if ($weightNDaysAgo) {
            $recentWeight = (float) $lastWeight->average_weight;
            $weightNDaysAgoVal = (float) $weightNDaysAgo->average_weight;
            $actualDays = (int) $weightNDaysAgo->date->diffInDays($lastWeight->date, true);

            if ($actualDays > 0) {
                $adg = ($recentWeight - $weightNDaysAgoVal) / $actualDays;
                $interpolated = $recentWeight + ($adg * $daysSince);
                return max(0, (float) $interpolated);
            }
        }

        return (float) $lastWeight->average_weight;
    }

    public static function getDressedWeightPerBird(Batch $batch): float
    {
        $liveWeight = self::getCurrentAverageWeight($batch);
        $dressPercentage = (float) SystemVariable::getValue('dress_percentage', 75);
        return $liveWeight * ($dressPercentage / 100);
    }

    public static function getCostPerBird(Batch $batch): float
    {
        $totalInvestment = self::calculateTotalInvestment($batch);
        $unallocated = $totalInvestment - (float) $batch->cost_allocated_so_far;
        return $batch->remaining_flock > 0 ? max(0, $unallocated / $batch->remaining_flock) : 0;
    }

    public static function getCostPerKg(Batch $batch): float
    {
        $costPerBird = self::getCostPerBird($batch);
        $dressedWeight = self::getDressedWeightPerBird($batch);
        return $dressedWeight > 0 ? $costPerBird / $dressedWeight : 0;
    }

    public static function getSellingPricePerBird(Batch $batch): float
    {
        $costPerBird = self::getCostPerBird($batch);
        $profitMargin = (float) SystemVariable::getValue('profit_margin', 20);
        return $costPerBird * (1 + ($profitMargin / 100));
    }

    public static function getCalculatedSellingPricePerKg(Batch $batch): float
    {
        $sellingPricePerBird = self::getSellingPricePerBird($batch);
        $dressedWeight = self::getDressedWeightPerBird($batch);
        return $dressedWeight > 0 ? $sellingPricePerBird / $dressedWeight : 0;
    }

    public static function getFinancialMetrics(Batch $batch): array
    {
        $costPerBird = self::getCostPerBird($batch);
        $costPerKg = self::getCostPerKg($batch);
        $sellingPricePerBird = self::getSellingPricePerBird($batch);
        $sellingPricePerKg = self::getCalculatedSellingPricePerKg($batch);
        $sellingPricePerCarton = $sellingPricePerKg * 10;

        return [
            'cost_per_bird'              => round($costPerBird, 2),
            'cost_per_kg'                => round($costPerKg, 2),
            'selling_price_per_bird'     => round($sellingPricePerBird, 2),
            'selling_price_per_kg'       => round($sellingPricePerKg, 2),
            'selling_price_per_carton'   => round($sellingPricePerCarton, 2),
            'current_live_weight_kg'     => round(self::getCurrentAverageWeight($batch), 3),
            'current_dressed_weight_kg'  => round(self::getDressedWeightPerBird($batch), 3),
            'profit_margin_percent'      => SystemVariable::getValue('profit_margin', 20),
            'dress_percentage'           => SystemVariable::getValue('dress_percentage', 75),
            'remaining_flock'            => $batch->remaining_flock,
        ];
    }

    /**
     * Total cost basis of the flock currently in this batch.
     *
     *   initial chicken cost
     * + expenses
     * + feed records (single source of truth for feed cost)
     * + manual non-feed, non-waste consumptions
     * + transfer_out cost deltas (negative)
     * + transfer_in cost deltas (positive)
     *
     * Feed-sourced inventory consumptions are stock trail only and must not
     * contribute again. Waste rows are stock losses and never inflate cost.
     * Transfers adjust the basis because birds and their cost physically move.
     */
    public static function calculateTotalInvestment(Batch $batch): float
    {
        $total = (float) ($batch->initial_chicken_cost ?? 0);

        if (! $batch->exists) {
            return $total;
        }

        if (! Schema::hasTable('expenses')
            || ! Schema::hasTable('inventory_consumptions')
            || ! Schema::hasTable('feed_records')
            || ! Schema::hasTable('batch_state_migrations')) {
            return $total;
        }

        $expenses = (float) ($batch->expenses()->sum('amount') ?? 0);
        $feedCost = (float) ($batch->feedRecords()->sum('total_feed_cost') ?? 0);
        $manualConsumptions = (float) ($batch->inventoryConsumptions()
            ->whereNotIn('source_type', ['waste', 'feed'])
            ->sum('total_cost') ?? 0);

        $transferOutCost = (float) BatchStateMigration::where('source_batch_id', $batch->id)
            ->where('migration_type', 'transfer_out')
            ->sum('cost_moved');

        $transferInCost = (float) BatchStateMigration::where('destination_batch_id', $batch->id)
            ->where('migration_type', 'transfer_in')
            ->sum('cost_moved');

        $total = $total
            + $expenses
            + $feedCost
            + $manualConsumptions
            + $transferOutCost
            + $transferInCost;

        return max(0, $total);
    }

    public static function allocateCostForSlaughter(Batch $batch, int $numberSlaughtered, ?int $oldRemaining = null, ?float $oldTotalInvestment = null): float
    {
        if ($numberSlaughtered <= 0) {
            return 0;
        }

        $oldRemaining = $oldRemaining ?? $batch->remaining_flock;
        $oldTotalInvestment = $oldTotalInvestment ?? self::calculateTotalInvestment($batch);

        $unallocated = $oldTotalInvestment - (float) $batch->cost_allocated_so_far;
        if ($oldRemaining <= 0 || $unallocated <= 0) {
            return 0;
        }

        $costPerBird = $unallocated / $oldRemaining;
        $allocated = $costPerBird * $numberSlaughtered;

        $batch->cost_allocated_so_far += $allocated;
        if ($batch->cost_allocated_so_far > $oldTotalInvestment) {
            $batch->cost_allocated_so_far = $oldTotalInvestment;
        }
        $batch->save();

        return $allocated;
    }

    public static function updateCachedMetrics(Batch $batch)
    {
        DB::transaction(function () use ($batch) {
            $batch->current_age_days = $batch->start_date
                ? max(0, (int) $batch->start_date->diffInDays(Carbon::today(), true))
                : 0;

            $totals = $batch->flockRecords()
                ->selectRaw('SUM(culls) as total_culls, SUM(slaughter) as total_slaughter')
                ->first();

            $batch->total_culls     = $totals->total_culls ?? 0;
            $batch->total_slaughter = $totals->total_slaughter ?? 0;

            $feedTotal = $batch->feedRecords()->sum('feed_used') ?? 0;
            $batch->total_feed_used = (float) $feedTotal;
            $batch->bags_consumed   = $feedTotal > 0 ? $feedTotal / 25 : 0;

            $batch->total_weight_gain = self::calculateTotalWeightGain($batch);

            $batch->current_cfcr = ($batch->total_feed_used > 0 && $batch->total_weight_gain > 0)
                ? $batch->total_feed_used / $batch->total_weight_gain
                : 0;
            $batch->current_ifcr = self::calculateIFCR($batch);

            $expenseTotal = $batch->expenses()->sum('amount') ?? 0;
            $batch->total_expenses = (float) $expenseTotal;

            $batch->current_marginal_profit_percent = self::calculateDailyMarginalProfitPercent($batch);

            $currentProfit = self::calculateCurrentProfit($batch);
            if ($currentProfit > $batch->peak_profit) {
                $batch->peak_profit = $currentProfit;
            }

            $retracement = $batch->peak_profit - $currentProfit;
            $stopLossAmount = (float) SystemVariable::getValue('stop_loss_amount', 20000);
            $batch->stop_loss_used_percent = $stopLossAmount > 0
                ? min(100, ($retracement / $stopLossAmount) * 100)
                : 0;

            $costPerBird = $batch->getCostPerBird();
            $sellingPricePerBird = $batch->getSellingPricePerBird();
            $batch->profit_margin_used = $costPerBird > 0
                ? (($sellingPricePerBird - $costPerBird) / $costPerBird) * 100
                : 0;

            $batch->selling_price_per_kg     = $batch->getCalculatedSellingPricePerKg();
            $batch->selling_price_per_carton = $batch->selling_price_per_kg * 10;

            $batch->mortality_rate = $batch->starting_flock > 0
                ? ($batch->total_mortality / $batch->starting_flock) * 100
                : 0;

            $batch->save();
        });
    }

    private static function calculateTotalWeightGain(Batch $batch): float
    {
        $records = $batch->weightRecords()->orderBy('date')->get();
        if ($records->count() < 2) {
            return 0;
        }

        $totalGain = 0.0;

        for ($i = 0; $i < $records->count() - 1; $i++) {
            $current = $records[$i];
            $next    = $records[$i + 1];

            $daysBetween = (int) $current->date->diffInDays($next->date, true);
            if ($daysBetween <= 0) {
                continue;
            }

            $adg = ((float) $next->average_weight - (float) $current->average_weight) / $daysBetween;

            $avgFlock = ($batch->starting_flock + $batch->remaining_flock) / 2;
            $totalGain += $adg * $avgFlock * $daysBetween;
        }

        return $totalGain;
    }

    private static function calculateIFCR(Batch $batch): float
    {
        $n = (int) SystemVariable::getValue('weighing_frequency_days', 4);
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays($n);

        $recentFeed = (float) ($batch->feedRecords()
            ->where('date', '>=', $startDate)
            ->sum('feed_used') ?? 0);

        $weightRecords = $batch->weightRecords()
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->get();

        if ($weightRecords->count() < 2 || $recentFeed <= 0) {
            return 0;
        }

        $first = $weightRecords->first();
        $last  = $weightRecords->last();

        $weightGainTotal = ((float) $last->average_weight - (float) $first->average_weight)
            * max(1, (int) $batch->remaining_flock);

        if ($weightGainTotal <= 0) {
            return 0;
        }

        return $recentFeed / $weightGainTotal;
    }

    private static function calculateDailyMarginalProfitPercent(Batch $batch): float
    {
        $records = $batch->weightRecords()->latest('date')->limit(2)->get();
        if ($records->count() < 2) {
            return 0;
        }

        $recent   = $records[0];
        $previous = $records[1];

        $daysBetween = (int) $previous->date->diffInDays($recent->date, true);
        if ($daysBetween <= 0) {
            return 0;
        }

        $adg = ((float) $recent->average_weight - (float) $previous->average_weight) / $daysBetween;

        $feedUsed = $batch->feedRecords()
            ->whereBetween('date', [$previous->date, $recent->date])
            ->sum('feed_used') ?? 0;

        $dailyFeedPerBird = $batch->remaining_flock > 0
            ? $feedUsed / ($daysBetween * $batch->remaining_flock)
            : 0;

        $avgFeedCost = $batch->feedRecords()
            ->whereBetween('date', [$previous->date, $recent->date])
            ->avg('feed_cost_per_kg') ?? 0;

        $dailyFeedCostPerBird = $dailyFeedPerBird * $avgFeedCost;

        $dressPct = (float) SystemVariable::getValue('dress_percentage', 75) / 100;
        $sellingPricePerKg = self::getCalculatedSellingPricePerKg($batch);

        $marginalDailyProfit = ($adg * $dressPct * $sellingPricePerKg) - $dailyFeedCostPerBird;

        if ($dailyFeedCostPerBird > 0) {
            return ($marginalDailyProfit / $dailyFeedCostPerBird) * 100;
        }

        return 0;
    }

    private static function calculateCurrentProfit(Batch $batch): float
    {
        $currentWeight = self::getCurrentAverageWeight($batch);
        if ($currentWeight <= 0 || $batch->remaining_flock <= 0) {
            return 0;
        }

        $dressedWeight     = self::getDressedWeightPerBird($batch);
        $sellingPricePerKg = self::getCalculatedSellingPricePerKg($batch);

        $revenue   = $dressedWeight * $batch->remaining_flock * $sellingPricePerKg;
        $totalCost = self::calculateTotalInvestment($batch) - (float) $batch->cost_allocated_so_far;

        return $revenue - $totalCost;
    }
}