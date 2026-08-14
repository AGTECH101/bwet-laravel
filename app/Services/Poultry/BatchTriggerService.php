<?php

namespace App\Services\Poultry;

use App\Models\Poultry\Batch;
use App\Models\SystemVariable;
use Carbon\Carbon;

class BatchTriggerService
{
    public static function checkSlaughterTriggers(Batch $batch): array
    {
        $triggers = [];

        $dailyProfitTolerance = SystemVariable::getValue('daily_profit_tolerance', -15);
        $fcrEfficiencyTolerance = SystemVariable::getValue('fcr_efficiency_tolerance', 20);
        $stopLossAmount = SystemVariable::getValue('stop_loss_amount', 20000);

        // 1. Daily profit tolerance
        if ($batch->current_marginal_profit_percent <= $dailyProfitTolerance) {
            $triggers[] = [
                'type' => 'daily_profit',
                'message' => "Daily profit at {$batch->current_marginal_profit_percent}%, below tolerance of {$dailyProfitTolerance}%",
                'severity' => 'critical',
            ];
        }

        // 2. FCR efficiency drop
        if ($batch->current_cfcr > 0) {
            $efficiencyDrop = (($batch->current_ifcr / $batch->current_cfcr) - 1) * 100;
            if ($efficiencyDrop >= $fcrEfficiencyTolerance) {
                $triggers[] = [
                    'type' => 'fcr_efficiency',
                    'message' => "FCR efficiency dropped by " . round($efficiencyDrop,1) . "%, above tolerance of {$fcrEfficiencyTolerance}%",
                    'severity' => 'warning',
                ];
            }
        }

        // 3. Stop-loss
        $currentProfit = self::calculateCurrentProfit($batch);
        $retracement = $batch->peak_profit - $currentProfit;
        if ($retracement >= $stopLossAmount) {
            $triggers[] = [
                'type' => 'stop_loss',
                'message' => "Retracement of ₦" . number_format($retracement,0) . " from peak, above stop-loss of ₦" . number_format($stopLossAmount,0),
                'severity' => 'critical',
            ];
        }

        // 4. Emergency
        $emergency = self::checkEmergencyTriggers($batch);
        $triggers = array_merge($triggers, $emergency);

        return $triggers;
    }

    private static function calculateCurrentProfit(Batch $batch): float
    {
        $currentWeight = BatchCalculationService::getCurrentAverageWeight($batch);
        if ($currentWeight <= 0 || $batch->remaining_flock <= 0) return 0;

        $dressedWeight = BatchCalculationService::getDressedWeightPerBird($batch);
        $sellingPricePerKg = BatchCalculationService::getCalculatedSellingPricePerKg($batch);
        $revenue = $dressedWeight * $batch->remaining_flock * $sellingPricePerKg;
        $totalCost = BatchCalculationService::calculateTotalInvestment($batch) - $batch->cost_allocated_so_far;
        return $revenue - $totalCost;
    }

    private static function checkEmergencyTriggers(Batch $batch): array
    {
        $triggers = [];

        $missedCount = $batch->weighingSchedules()
            ->where('scheduled_date', '<', Carbon::today())
            ->where('is_completed', false)
            ->count();

        if ($missedCount >= 3) {
            $triggers[] = [
                'type' => 'missed_weighings',
                'message' => "Missed {$missedCount} consecutive weighings. Switching to manual mode.",
                'severity' => 'emergency',
                'action' => 'switch_to_manual',
            ];
        }

        // Weight loss > 5%
        $weightLoss = self::checkWeightLoss($batch);
        if ($weightLoss >= 5) {
            $triggers[] = [
                'type' => 'weight_loss',
                'message' => "Weight loss of " . round($weightLoss,1) . "% detected. Switching to manual mode.",
                'severity' => 'emergency',
                'action' => 'switch_to_manual',
            ];
        }

        $mortalityRate = $batch->starting_flock > 0 ? ($batch->total_mortality / $batch->starting_flock) * 100 : 0;
        if ($mortalityRate >= 7) {
            $triggers[] = [
                'type' => 'high_mortality',
                'message' => "Mortality rate of " . round($mortalityRate,1) . "% detected. Switching to manual mode.",
                'severity' => 'emergency',
                'action' => 'switch_to_manual',
            ];
        }

        return $triggers;
    }

    private static function checkWeightLoss(Batch $batch): float
    {
        $records = $batch->weightRecords()->latest('date')->limit(2)->get();
        if ($records->count() < 2) return 0;
        $recent = $records[0]->average_weight;
        $prev = $records[1]->average_weight;
        if ($prev > 0) {
            return max(0, (($prev - $recent) / $prev) * 100);
        }
        return 0;
    }
}