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

        $batches = Batch::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        $selectedBatch = null;
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
            'batch_id' => 'nullable|exists:poultry_batches,id',
            'customer_bird_weight' => 'required|numeric|min:0.001',
            'mode_weight' => 'required|numeric|min:0.001',
            'profit_margin' => 'nullable|numeric|min:0|max:100',
        ]);

        $batch = Batch::find($request->batch_id);

        if (!$batch) {
            return response()->json(['error' => 'Please select a valid batch.'], 422);
        }

        $customerWeight = (float) $request->customer_bird_weight;
        $modeWeight = (float) $request->mode_weight;
        $profitMargin = (float) ($request->profit_margin ?? SystemVariable::getValue('profit_margin', 20));

        // Use the checkpoint state
        $avgCost = $batch->current_average_cost;

        // Calculate the selling price per bird
        $costScaled = ($customerWeight / $modeWeight) * $avgCost;
        $sellingPricePerBird = $costScaled * (1 + $profitMargin / 100);

        // Dressed weight (if needed)
        $dressPercentage = SystemVariable::getValue('dress_percentage', 75);
        $dressedWeight = $customerWeight * ($dressPercentage / 100);
        $sellingPricePerKg = $dressedWeight > 0 ? $sellingPricePerBird / $dressedWeight : 0;
        $sellingPricePerCarton = $sellingPricePerKg * 10;

        return response()->json([
            'batch_id' => $batch->id,
            'batch_name' => $batch->batch_id . ' - ' . $batch->name,
            'customer_bird_weight' => round($customerWeight, 3),
            'mode_weight' => round($modeWeight, 3),
            'current_avg_cost' => round($avgCost, 2),
            'cost_scaled' => round($costScaled, 2),
            'profit_margin' => round($profitMargin, 1),
            'selling_price_per_bird' => round($sellingPricePerBird, 2),
            'selling_price_per_kg' => round($sellingPricePerKg, 2),
            'selling_price_per_carton' => round($sellingPricePerCarton, 2),
            'dress_percentage' => $dressPercentage,
        ]);
    }
}