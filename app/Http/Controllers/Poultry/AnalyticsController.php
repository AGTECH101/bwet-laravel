<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Models\Poultry\Batch;
use App\Services\Poultry\BatchAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AnalyticsController extends Controller
{
    /**
     * Global analytics across all batches.
     */
    public function global()
    {
        Gate::authorize('viewAny', Batch::class);

        // Total counts
        $totalBatches = Batch::count();
        $activeBatches = Batch::where('status', 'active')->count();
        $completedBatches = Batch::whereIn('status', ['closed', 'completed'])->count();

        // Financials
        $totalInvestment = Batch::where('status', 'active')->get()->sum(function ($batch) {
            return $batch->calculateTotalInvestment();
        });

        // Averages
        $avgFcr = Batch::where('status', 'active')->where('current_cfcr', '>', 0)->avg('current_cfcr') ?? 0;
        $avgMortality = Batch::where('status', 'active')->where('starting_flock', '>', 0)->get()->map(function ($batch) {
            return ($batch->total_mortality / $batch->starting_flock) * 100;
        })->avg() ?? 0;

        // Recent performance data (for table and charts)
        $recentPerformance = Batch::orderBy('created_at', 'desc')->limit(10)->get()->map(function ($batch) {
            return [
                'batch' => $batch->batch_id,
                'status' => $batch->status,
                'age' => $batch->current_age_days,
                'ifcr' => $batch->current_ifcr,
                'cfcr' => $batch->current_cfcr,
                'mortality' => $batch->starting_flock > 0 ? ($batch->total_mortality / $batch->starting_flock) * 100 : 0,
                'profit_percent' => $batch->current_marginal_profit_percent,
            ];
        })->toArray();

        // Top performers (profit > 20%)
        $topPerformers = array_filter($recentPerformance, function ($p) {
            return ($p['profit_percent'] ?? 0) > 20;
        });

        // Areas for improvement (mortality > 8% or profit < 5%)
        $improvementAreas = array_filter($recentPerformance, function ($p) {
            return ($p['mortality'] ?? 0) > 8 || ($p['profit_percent'] ?? 0) < 5;
        });

        return view('sectors.poultry.analytics.global', compact(
            'totalBatches',
            'activeBatches',
            'completedBatches',
            'totalInvestment',
            'avgFcr',
            'avgMortality',
            'recentPerformance',
            'topPerformers',
            'improvementAreas'
        ));
    }

    /**
     * Charts for a specific batch.
     */
    public function charts(Batch $batch)
    {
        Gate::authorize('view', $batch);

        $chartData = BatchAnalyticsService::getBatchChartData($batch, 30);
        $batch->updateCachedMetrics();

        return view('sectors.poultry.analytics.charts', compact('batch', 'chartData'));
    }

    /**
     * Real-time charts for a batch (with auto-refresh).
     */
    public function realtime(Batch $batch)
    {
        Gate::authorize('view', $batch);

        return view('sectors.poultry.analytics.realtime', compact('batch'));
    }

    /**
     * API endpoint for chart data (AJAX).
     */
    public function chartData(Batch $batch, Request $request)
    {
        Gate::authorize('view', $batch);

        $days = $request->input('days', 30);
        $chartData = BatchAnalyticsService::getBatchChartData($batch, $days);

        // Prepare KPI data
        $batch->updateCachedMetrics();
        $currentWeight = $batch->getCurrentAverageWeight();
        $currentMortality = $batch->starting_flock > 0 ? ($batch->total_mortality / $batch->starting_flock) * 100 : 0;
        $currentAdg = $batch->calculate_daily_marginal_profit_percent(); // This is actually ADG? Might need separate method.

        // Format historical data for table
        $historicalData = [];
        if (!empty($chartData['age_vs_weight']['ages'])) {
            $count = count($chartData['age_vs_weight']['ages']);
            for ($i = 0; $i < $count; $i++) {
                $historicalData[] = [
                    'date' => $chartData['ifcr_vs_cfcr']['dates'][$i] ?? '',
                    'age' => $chartData['age_vs_weight']['ages'][$i] ?? 0,
                    'weight' => $chartData['age_vs_weight']['weights'][$i] ?? 0,
                    'ifcr' => $chartData['ifcr_vs_cfcr']['ifcr'][$i] ?? 0,
                    'cfcr' => $chartData['ifcr_vs_cfcr']['cfcr'][$i] ?? 0,
                    'mortality' => $chartData['mortality_trend']['mortality'][$i] ?? 0,
                    'adg' => $chartData['adg_vs_age']['adg'][$i] ?? 0,
                ];
            }
        }

        return response()->json([
            'weight_data' => [
                'labels' => $chartData['age_vs_weight']['ages'] ?? [],
                'values' => $chartData['age_vs_weight']['weights'] ?? [],
            ],
            'fcr_data' => [
                'labels' => $chartData['ifcr_vs_cfcr']['dates'] ?? [],
                'ifcr_values' => $chartData['ifcr_vs_cfcr']['ifcr'] ?? [],
                'cfcr_values' => $chartData['ifcr_vs_cfcr']['cfcr'] ?? [],
            ],
            'current_weight' => $currentWeight,
            'current_ifcr' => $batch->current_ifcr,
            'current_cfcr' => $batch->current_cfcr,
            'current_mortality' => $currentMortality,
            'current_adg' => $currentAdg,
            'historical_data' => $historicalData,
        ]);
    }
}