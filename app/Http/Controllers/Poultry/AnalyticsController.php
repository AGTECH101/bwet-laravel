<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Models\Poultry\Batch;
use App\Services\Poultry\BatchAnalyticsService;
use App\Services\Poultry\BatchCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AnalyticsController extends Controller
{
    public function global()
    {
        Gate::authorize('viewAny', Batch::class);

        // Refresh checkpoint fields on every batch we're about to read from.
        Batch::where('status', 'active')->chunk(50, function ($batches) {
            foreach ($batches as $batch) {
                $batch->updateCachedMetrics();
            }
        });

        $totalBatches     = Batch::count();
        $activeBatches    = Batch::where('status', 'active')->count();
        $completedBatches = Batch::whereIn('status', ['closed', 'completed'])->count();

        $activeBatchModels = Batch::where('status', 'active')->get();

        $totalInvestment = $activeBatchModels->sum(function ($batch) {
            return BatchCalculationService::calculateTotalInvestment($batch);
        });

        $avgFcr = $activeBatchModels->where('current_cfcr', '>', 0)->avg('current_cfcr') ?? 0;

        $avgMortality = $activeBatchModels
            ->filter(fn ($b) => $b->starting_flock > 0)
            ->avg(fn ($b) => ($b->total_mortality / $b->starting_flock) * 100) ?? 0;

        $recentPerformance = Batch::orderBy('created_at', 'desc')->limit(10)->get()->map(function ($batch) {
            return [
                'batch'          => $batch->batch_id,
                'status'         => $batch->status,
                'age'            => $batch->current_age_days,
                'ifcr'           => (float) $batch->current_ifcr,
                'cfcr'           => (float) $batch->current_cfcr,
                'mortality'      => $batch->starting_flock > 0
                    ? ($batch->total_mortality / $batch->starting_flock) * 100
                    : 0,
                'profit_percent' => (float) $batch->current_marginal_profit_percent,
            ];
        })->toArray();

        // Chart-ready data: one entry per recent batch.
        $fcrChart = [
            'labels' => array_map(fn ($p) => $p['batch'], $recentPerformance),
            'ifcr'   => array_map(fn ($p) => $p['ifcr'], $recentPerformance),
            'cfcr'   => array_map(fn ($p) => $p['cfcr'], $recentPerformance),
        ];

        // Bucket recent batches by mortality rate for the distribution chart.
        $mortalityBuckets = [
            '< 3%'   => 0,
            '3-5%'   => 0,
            '5-8%'   => 0,
            '8-10%'  => 0,
            '> 10%'  => 0,
        ];
        foreach ($recentPerformance as $p) {
            $m = $p['mortality'];
            if ($m < 3)       $mortalityBuckets['< 3%']++;
            elseif ($m < 5)   $mortalityBuckets['3-5%']++;
            elseif ($m < 8)   $mortalityBuckets['5-8%']++;
            elseif ($m < 10)  $mortalityBuckets['8-10%']++;
            else              $mortalityBuckets['> 10%']++;
        }

        $topPerformers = array_values(array_filter($recentPerformance, fn ($p) => ($p['profit_percent'] ?? 0) > 20));
        $improvementAreas = array_values(array_filter($recentPerformance, fn ($p) => ($p['mortality'] ?? 0) > 8 || ($p['profit_percent'] ?? 0) < 5));

        return view('sectors.poultry.analytics.global', compact(
            'totalBatches',
            'activeBatches',
            'completedBatches',
            'totalInvestment',
            'avgFcr',
            'avgMortality',
            'recentPerformance',
            'topPerformers',
            'improvementAreas',
            'fcrChart',
            'mortalityBuckets'
        ));
    }

    public function charts(Batch $batch)
    {
        Gate::authorize('view', $batch);

        $chartData = BatchAnalyticsService::getBatchChartData($batch, 30);
        $batch->updateCachedMetrics();
        $batch->refresh();

        return view('sectors.poultry.analytics.charts', compact('batch', 'chartData'));
    }

    public function realtime(Batch $batch)
    {
        Gate::authorize('view', $batch);

        return view('sectors.poultry.analytics.realtime', compact('batch'));
    }

    public function chartData(Batch $batch, Request $request)
    {
        Gate::authorize('view', $batch);

        $days = max(1, min(365, (int) $request->input('days', 30)));
        $chartData = BatchAnalyticsService::getBatchChartData($batch, $days);

        $batch->updateCachedMetrics();
        $batch->refresh();

        $currentWeight = $batch->getCurrentAverageWeight();
        $currentMortality = $batch->starting_flock > 0
            ? ($batch->total_mortality / $batch->starting_flock) * 100
            : 0;

        $recentWeights = $batch->weightRecords()
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(2)
            ->get();

        $currentAdg = 0.0;
        if ($recentWeights->count() >= 2) {
            $latest   = $recentWeights[0];
            $previous = $recentWeights[1];
            $daysBetween = (int) $previous->date->diffInDays($latest->date, true);
            if ($daysBetween > 0) {
                $currentAdg = ((float) $latest->average_weight - (float) $previous->average_weight) / $daysBetween;
            }
        }

        return response()->json([
            'weight_data' => [
                'labels' => $chartData['age_vs_weight']['ages'] ?? [],
                'values' => $chartData['age_vs_weight']['weights'] ?? [],
            ],
            'fcr_data' => [
                'labels'      => $chartData['ifcr_vs_cfcr']['labels'] ?? [],
                'ifcr_values' => $chartData['ifcr_vs_cfcr']['ifcr'] ?? [],
                'cfcr_values' => $chartData['ifcr_vs_cfcr']['cfcr'] ?? [],
            ],
            'current_weight'    => $currentWeight,
            'current_ifcr'      => (float) $batch->current_ifcr,
            'current_cfcr'      => (float) $batch->current_cfcr,
            'current_mortality' => $currentMortality,
            'current_adg'       => $currentAdg,
            'historical_data'   => array_map(function ($m) {
                return [
                    'date'      => $m['date'],
                    'age'       => $m['age_days'],
                    'weight'    => $m['average_weight'],
                    'ifcr'      => $m['ifcr'],
                    'cfcr'      => $m['cfcr'],
                    'mortality' => $m['mortality'],
                    'adg'       => $m['adg'],
                ];
            }, $chartData['metrics'] ?? []),
        ]);
    }
}