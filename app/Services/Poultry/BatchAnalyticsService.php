<?php

namespace App\Services\Poultry;

use App\Models\Poultry\Batch;
use Carbon\Carbon;

class BatchAnalyticsService
{
    /**
     * Build chart-ready and table-ready data for a batch.
     *
     * Returns:
     *   - ifcr_vs_cfcr:      ['labels' => [], 'ifcr' => [], 'cfcr' => []]
     *   - adg_vs_age:        ['dates' => [], 'adg' => [], 'target_adg' => []]
     *   - age_vs_weight:     ['ages' => [], 'weights' => []]
     *   - mortality_trend:   ['dates' => [], 'mortality' => []]
     *   - metrics:           flat rows for the data table
     *   - no_data:           true if the batch has no weight records in range
     */
    public static function getBatchChartData(Batch $batch, int $days = 30): array
    {
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays($days);

        $weightRecords = $batch->weightRecords()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        if ($weightRecords->isEmpty()) {
            return [
                'ifcr_vs_cfcr'    => ['labels' => [], 'ifcr' => [], 'cfcr' => []],
                'adg_vs_age'      => ['dates' => [], 'adg' => [], 'target_adg' => []],
                'age_vs_weight'   => ['ages' => [], 'weights' => []],
                'mortality_trend' => ['dates' => [], 'mortality' => []],
                'metrics'         => [],
                'no_data'         => true,
            ];
        }

        $chartData = [
            'ifcr_vs_cfcr'    => ['labels' => [], 'ifcr' => [], 'cfcr' => []],
            'adg_vs_age'      => ['dates' => [], 'adg' => [], 'target_adg' => []],
            'age_vs_weight'   => ['ages' => [], 'weights' => []],
            'mortality_trend' => ['dates' => [], 'mortality' => []],
            'metrics'         => [],
            'no_data'         => false,
        ];

        $list = $weightRecords->values();
        $batchStart = $batch->start_date;

        for ($i = 0; $i < $list->count(); $i++) {
            $record = $list[$i];
            $dateLabel = $record->date->format('m-d');
            $age = $batchStart ? (int) $batchStart->diffInDays($record->date, true) : 0;

            // Age vs Weight
            $chartData['age_vs_weight']['ages'][] = $age;
            $chartData['age_vs_weight']['weights'][] = (float) $record->average_weight;

            // Cumulative feed up to this date
            $cumulativeFeed = (float) $batch->feedRecords()
                ->where('date', '<=', $record->date)
                ->sum('feed_used');

            // Cumulative weight gain estimate for CFCR
            $cumulativeWeightGain = ((float) $record->average_weight - 0.045) * max(1, (int) $batch->remaining_flock);
            $cfcr = $cumulativeWeightGain > 0 ? $cumulativeFeed / $cumulativeWeightGain : 0;

            $chartData['ifcr_vs_cfcr']['labels'][] = $dateLabel;
            $chartData['ifcr_vs_cfcr']['cfcr'][] = round((float) $cfcr, 4);

            // Interval FCR (feed between consecutive weight records / gain)
            if ($i > 0) {
                $prev = $list[$i - 1];
                $daysBetween = (int) $prev->date->diffInDays($record->date, true);
                $weightGain = (float) $record->average_weight - (float) $prev->average_weight;
                $feedInPeriod = (float) $batch->feedRecords()
                    ->whereBetween('date', [$prev->date, $record->date])
                    ->sum('feed_used');

                $ifcr = ($weightGain > 0 && $daysBetween > 0 && $batch->remaining_flock > 0)
                    ? $feedInPeriod / ($weightGain * max(1, (int) $batch->remaining_flock))
                    : 0;

                $chartData['ifcr_vs_cfcr']['ifcr'][] = round((float) $ifcr, 4);

                $adg = $daysBetween > 0 ? $weightGain / $daysBetween : 0;
            } else {
                // First record: measure from day 0 (chick weight)
                $weightGain = (float) $record->average_weight - 0.045;
                $feedFromStart = $cumulativeFeed;
                $ifcr = ($weightGain > 0 && $age > 0 && $batch->remaining_flock > 0)
                    ? $feedFromStart / ($weightGain * max(1, (int) $batch->remaining_flock))
                    : 0;

                $chartData['ifcr_vs_cfcr']['ifcr'][] = round((float) $ifcr, 4);

                $adg = $age > 0 ? $weightGain / $age : 0;
            }

            $chartData['adg_vs_age']['dates'][] = $dateLabel;
            $chartData['adg_vs_age']['adg'][] = round((float) $adg, 4);
            $chartData['adg_vs_age']['target_adg'][] = 0.065;

            // Cumulative mortality up to this date
            $mortalityToDate = (int) $batch->flockRecords()
                ->where('date', '<=', $record->date)
                ->sum('mortality');
            $mortalityRate = $batch->starting_flock > 0
                ? ($mortalityToDate / $batch->starting_flock) * 100
                : 0;

            $chartData['mortality_trend']['dates'][] = $dateLabel;
            $chartData['mortality_trend']['mortality'][] = round((float) $mortalityRate, 2);

            // Row for the data table
            $chartData['metrics'][] = [
                'date'           => $record->date->toDateString(),
                'age_days'       => $age,
                'average_weight' => (float) $record->average_weight,
                'ifcr'           => (float) end($chartData['ifcr_vs_cfcr']['ifcr']),
                'cfcr'           => (float) end($chartData['ifcr_vs_cfcr']['cfcr']),
                'adg'            => (float) $adg,
                'mortality'      => (float) $mortalityRate,
            ];
        }

        return $chartData;
    }
}