<?php

namespace App\Services\Poultry;

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
        if (!$lastWeight) return 0.0;

        $daysSince = Carbon::today()->diffInDays($lastWeight->date);
        $n = (int) SystemVariable::getValue('weighing_frequency_days', 4);

        $weightNDaysAgo = $batch->weightRecords()
            ->where('date', '<=', $lastWeight->date->copy()->subDays($n))
            ->latest('date')
            ->first();

        if ($weightNDaysAgo) {
            $recentWeight = $lastWeight->average_weight;
            $weightNDaysAgoVal = $weightNDaysAgo->average_weight;
            $actualDays = Carbon::parse($lastWeight->date)->diffInDays($weightNDaysAgo->date);
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
        $dressPercentage = SystemVariable::getValue('dress_percentage', 75);
        return $liveWeight * ($dressPercentage / 100);
    }

    public static function getCostPerBird(Batch $batch): float
    {
        $totalInvestment = self::calculateTotalInvestment($batch);
        $unallocated = $totalInvestment - $batch->cost_allocated_so_far;
        return $batch->remaining_flock > 0 ? $unallocated / $batch->remaining_flock : 0;
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
        $profitMargin = SystemVariable::getValue('profit_margin', 20);
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
            'cost_per_bird' => round($costPerBird, 2),
            'cost_per_kg' => round($costPerKg, 2),
            'selling_price_per_bird' => round($sellingPricePerBird, 2),
            'selling_price_per_kg' => round($sellingPricePerKg, 2),
            'selling_price_per_carton' => round($sellingPricePerCarton, 2),
            'current_live_weight_kg' => round(self::getCurrentAverageWeight($batch), 3),
            'current_dressed_weight_kg' => round(self::getDressedWeightPerBird($batch), 3),
            'profit_margin_percent' => SystemVariable::getValue('profit_margin', 20),
            'dress_percentage' => SystemVariable::getValue('dress_percentage', 75),
            'remaining_flock' => $batch->remaining_flock,
        ];
    }

    public static function calculateTotalInvestment(Batch $batch): float
    {
        $total = (float) ($batch->initial_chicken_cost ?? 0);

        if (! $batch->exists) {
            return $total;
        }

        if (! Schema::hasTable('expenses') || ! Schema::hasTable('inventory_consumptions')) {
            return $total;
        }

        $expenses = (float) ($batch->expenses()->sum('amount') ?? 0);
        $consumptions = (float) ($batch->inventoryConsumptions()
            ->where('source_type', '!=', 'waste')
            ->sum('total_cost') ?? 0);

        return $total + $expenses + $consumptions;
    }

    public static function allocateCostForSlaughter(Batch $batch, int $numberSlaughtered, ?int $oldRemaining = null, ?float $oldTotalInvestment = null): float
    {
        if ($numberSlaughtered <= 0) return 0;

        $oldRemaining = $oldRemaining ?? $batch->remaining_flock;
        $oldTotalInvestment = $oldTotalInvestment ?? self::calculateTotalInvestment($batch);

        $unallocated = $oldTotalInvestment - $batch->cost_allocated_so_far;
        if ($oldRemaining <= 0 || $unallocated <= 0) return 0;

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
            // Age
            $batch->current_age_days = max(0, Carbon::today()->diffInDays($batch->start_date));

            // Flock totals (DO NOT recalculate remaining_flock – it's managed manually via transfers/flock records)
            $totals = $batch->flockRecords()
                ->selectRaw('SUM(mortality) as total_mort, SUM(culls) as total_culls, SUM(slaughter) as total_slaughter')
                ->first();

            $batch->total_mortality = $totals->total_mort ?? 0;
            $batch->total_culls = $totals->total_culls ?? 0;
            $batch->total_slaughter = $totals->total_slaughter ?? 0;

            // Feed totals
            $feedTotal = $batch->feedRecords()->sum('feed_used') ?? 0;
            $batch->total_feed_used = (float) $feedTotal;
            $batch->bags_consumed = $feedTotal > 0 ? $feedTotal / 25 : 0;

            // Weight gain (using interpolation)
            $batch->total_weight_gain = self::calculateTotalWeightGain($batch);

            // FCRs
            $batch->current_cfcr = ($batch->total_feed_used > 0 && $batch->total_weight_gain > 0)
                ? $batch->total_feed_used / $batch->total_weight_gain
                : 0;
            $batch->current_ifcr = self::calculateIFCR($batch);

            // Expenses
            $expenseTotal = $batch->expenses()->sum('amount') ?? 0;
            $batch->total_expenses = (float) $expenseTotal;

            // Profit
            $batch->current_marginal_profit_percent = self::calculateDailyMarginalProfitPercent($batch);

            // Peak profit and stop-loss
            $currentProfit = self::calculateCurrentProfit($batch);
            if ($currentProfit > $batch->peak_profit) {
                $batch->peak_profit = $currentProfit;
            }

            $retracement = $batch->peak_profit - $currentProfit;
            $stopLossAmount = SystemVariable::getValue('stop_loss_amount', 20000);
            $batch->stop_loss_used_percent = $stopLossAmount > 0 ? min(100, ($retracement / $stopLossAmount) * 100) : 0;

            // Profit margin used
            $costPerBird = self::getCostPerBird($batch);
            $sellingPricePerBird = self::getSellingPricePerBird($batch);
            $batch->profit_margin_used = $costPerBird > 0 ? (($sellingPricePerBird - $costPerBird) / $costPerBird) * 100 : 0;

            // Update selling price fields
            $batch->selling_price_per_kg = self::getCalculatedSellingPricePerKg($batch);
            $batch->selling_price_per_carton = $batch->selling_price_per_kg * 10;

            $batch->save();
        });
    }

    private static function calculateTotalWeightGain(Batch $batch): float
    {
        $records = $batch->weightRecords()->orderBy('date')->get();
        if ($records->count() < 2) return 0;

        $totalGain = 0.0;
        $n = SystemVariable::getValue('weighing_frequency_days', 4);

        for ($i = 0; $i < $records->count() - 1; $i++) {
            $current = $records[$i];
            $next = $records[$i+1];
            $daysBetween = $current->date->diffInDays($next->date);
            if ($daysBetween > 0) {
                $adg = ($next->average_weight - $current->average_weight) / $daysBetween;
                $avgFlock = ($batch->starting_flock + $batch->remaining_flock) / 2;
                $totalGain += $adg * $avgFlock * $daysBetween;
            }
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
        $last = $weightRecords->last();
        $weightGainTotal = ($last->average_weight - $first->average_weight) * max(1, $batch->remaining_flock);

        if ($weightGainTotal <= 0) {
            return 0;
        }

        return $recentFeed / $weightGainTotal;
    }

    private static function calculateDailyMarginalProfitPercent(Batch $batch): float
    {
        $records = $batch->weightRecords()->latest('date')->limit(2)->get();
        if ($records->count() < 2) return 0;

        $recent = $records[0];
        $previous = $records[1];
        $daysBetween = $recent->date->diffInDays($previous->date);
        if ($daysBetween <= 0) return 0;

        $adg = ($recent->average_weight - $previous->average_weight) / $daysBetween;

        $feedUsed = $batch->feedRecords()
            ->whereBetween('date', [$previous->date, $recent->date])
            ->sum('feed_used') ?? 0;

        $dailyFeedPerBird = $batch->remaining_flock > 0 ? $feedUsed / ($daysBetween * $batch->remaining_flock) : 0;

        $avgFeedCost = $batch->feedRecords()
            ->whereBetween('date', [$previous->date, $recent->date])
            ->avg('feed_cost_per_kg') ?? 0;

        $dailyFeedCostPerBird = $dailyFeedPerBird * $avgFeedCost;
        $sellingPricePerKg = self::getCalculatedSellingPricePerKg($batch);
        $marginalDailyProfit = ($adg * $sellingPricePerKg) - $dailyFeedCostPerBird;

        if ($dailyFeedCostPerBird > 0) {
            return ($marginalDailyProfit / $dailyFeedCostPerBird) * 100;
        }
        return 0;
    }

    private static function calculateCurrentProfit(Batch $batch): float
    {
        $currentWeight = self::getCurrentAverageWeight($batch);
        if ($currentWeight <= 0 || $batch->remaining_flock <= 0) return 0;

        $dressedWeight = self::getDressedWeightPerBird($batch);
        $sellingPricePerKg = self::getCalculatedSellingPricePerKg($batch);
        $revenue = $dressedWeight * $batch->remaining_flock * $sellingPricePerKg;
        $totalCost = self::calculateTotalInvestment($batch) - $batch->cost_allocated_so_far;
        return $revenue - $totalCost;
    }
}