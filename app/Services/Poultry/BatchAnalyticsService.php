<?php

namespace App\Services\Poultry;

use App\Models\Poultry\Batch;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BatchAnalyticsService
{
    public static function getBatchChartData(Batch $batch, int $days = 30): array
    {
        // Get weight records in date range
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays($days);

        $weightRecords = $batch->weightRecords()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        if ($weightRecords->isEmpty()) {
            return [
                'ifcr_vs_cfcr' => ['labels' => [], 'ifcr' => [], 'cfcr' => []],
                'adg_vs_age' => ['dates' => [], 'adg' => [], 'target_adg' => []],
                'age_vs_weight' => ['ages' => [], 'weights' => []],
                'mortality_trend' => ['dates' => [], 'mortality' => []],
                'no_data' => true,
            ];
        }

        $chartData = [
            'ifcr_vs_cfcr' => ['labels' => [], 'ifcr' => [], 'cfcr' => []],
            'adg_vs_age' => ['dates' => [], 'adg' => [], 'target_adg' => []],
            'age_vs_weight' => ['ages' => [], 'weights' => []],
            'mortality_trend' => ['dates' => [], 'mortality' => []],
            'no_data' => false,
        ];

        $list = $weightRecords->values();

        for ($i = 0; $i < $list->count(); $i++) {
            $record = $list[$i];
            $dateLabel = $record->date->format('m-d');
            $age = (int) $record->date->diffInDays($batch->start_date);

            // Age vs Weight
            $chartData['age_vs_weight']['ages'][] = $age;
            $chartData['age_vs_weight']['weights'][] = (float) $record->average_weight;

            // FCR data
            $cumulativeFeed = $batch->feedRecords()->where('date', '<=', $record->date)->sum('feed_used') ?? 0;
            $cumulativeWeightGain = ($record->average_weight - 0.045) * $batch->remaining_flock;
            $cfcr = $cumulativeWeightGain > 0 ? $cumulativeFeed / $cumulativeWeightGain : 0;

            $chartData['ifcr_vs_cfcr']['labels'][] = $dateLabel;
            $chartData['ifcr_vs_cfcr']['cfcr'][] = (float) $cfcr;

            if ($i > 0) {
                $prev = $list[$i - 1];
                $daysBetween = $record->date->diffInDays($prev->date);
                $weightGain = $record->average_weight - $prev->average_weight;
                $feedInPeriod = $batch->feedRecords()->whereBetween('date', [$prev->date, $record->date])->sum('feed_used') ?? 0;
                $ifcr = ($weightGain > 0 && $daysBetween > 0 && $batch->remaining_flock > 0)
                    ? $feedInPeriod / ($weightGain * $batch->remaining_flock)
                    : 0;
                $chartData['ifcr_vs_cfcr']['ifcr'][] = (float) $ifcr;
            } else {
                // First record - use day 0
                $daysFromStart = $age;
                $weightGain = $record->average_weight - 0.045;
                $feedFromStart = $batch->feedRecords()->where('date', '<=', $record->date)->sum('feed_used') ?? 0;
                $ifcr = ($weightGain > 0 && $daysFromStart > 0 && $batch->remaining_flock > 0)
                    ? $feedFromStart / ($weightGain * $batch->remaining_flock)
                    : 0;
                $chartData['ifcr_vs_cfcr']['ifcr'][] = (float) $ifcr;
            }

            // ADG
            if ($i > 0) {
                $prev = $list[$i - 1];
                $daysBetween = $record->date->diffInDays($prev->date);
                $adg = $daysBetween > 0 ? ($record->average_weight - $prev->average_weight) / $daysBetween : 0;
            } else {
                $adg = $age > 0 ? ($record->average_weight - 0.045) / $age : 0;
            }
            $chartData['adg_vs_age']['dates'][] = $dateLabel;
            $chartData['adg_vs_age']['adg'][] = (float) $adg;
            $chartData['adg_vs_age']['target_adg'][] = 0.065; // 65g/day

            // Mortality
            $mortalityToDate = $batch->flockRecords()->where('date', '<=', $record->date)->sum('mortality') ?? 0;
            $mortalityRate = $batch->starting_flock > 0 ? ($mortalityToDate / $batch->starting_flock) * 100 : 0;
            $chartData['mortality_trend']['dates'][] = $dateLabel;
            $chartData['mortality_trend']['mortality'][] = (float) $mortalityRate;
        }

        return $chartData;
    }
}