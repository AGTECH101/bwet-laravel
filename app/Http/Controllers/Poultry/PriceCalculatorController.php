<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Models\Poultry\Batch;
use App\Models\SystemVariable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PriceCalculatorController extends Controller
{
    public function create(Request $request)
    {
        Gate::authorize('viewAny', Batch::class);

        $selectedBatch = null;
        $batches = Batch::query()->orderBy('start_date', 'desc')->get();

        if ($request->filled('batch')) {
            $selectedBatch = Batch::where('batch_id', $request->batch)->first();
        }

        $defaultProfitMargin = SystemVariable::getValue('profit_margin', 20);
        $defaultDressPercentage = SystemVariable::getValue('dress_percentage', 75);

        return view('sectors.poultry.forms.price-calculator', compact(
            'batches',
            'selectedBatch',
            'defaultProfitMargin',
            'defaultDressPercentage'
        ));
    }

    public function calculate(Request $request)
    {
        Gate::authorize('viewAny', Batch::class);

        $request->validate([
            'selected_batch' => 'nullable|string',
            'cost_of_production' => 'required|numeric|min:0',
            'current_average_weight' => 'required|numeric|min:0.001',
            'mod_weight' => 'required|numeric|min:0.001',
        ]);

        $batch = null;
        if ($request->filled('selected_batch')) {
            $batch = Batch::where('batch_id', $request->selected_batch)->first();
        }

        $costOfProduction = (float) $request->cost_of_production;
        $currentAverageWeight = (float) $request->current_average_weight;
        $modeWeight = (float) $request->mod_weight;

        if ($modeWeight <= 0) {
            return response()->json(['error' => 'Mode weight must be greater than zero.'], 422);
        }

        $result = ($costOfProduction * $currentAverageWeight) / $modeWeight;

        return response()->json([
            'batch' => $batch?->batch_id ?? 'N/A',
            'cost_of_production' => round($costOfProduction, 2),
            'current_average_weight' => round($currentAverageWeight, 2),
            'mod_weight' => round($modeWeight, 2),
            'calculated_price' => round($result, 2),
            'formula' => '($cost_of_production * $current_average_weight) / $mod_weight',
        ]);
    }
}
