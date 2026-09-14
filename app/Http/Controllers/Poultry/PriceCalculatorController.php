<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Models\Poultry\Batch;
use App\Models\SystemVariable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

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

        $defaultProfitMargin    = (float) SystemVariable::getValue('profit_margin', 20);
        $defaultDressPercentage = (float) SystemVariable::getValue('dress_percentage', 75);

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

        $validated = $request->validate([
            'batch_id'             => 'required|exists:poultry_batches,id',
            'customer_bird_weight' => 'required|numeric|min:0.001|max:20',
            'mode_weight'          => 'required|numeric|min:0.001|max:20',
            'profit_margin'        => 'nullable|numeric|min:0|max:1000',
        ]);

        try {
            $batch = Batch::findOrFail($validated['batch_id']);

            $customerWeight = (float) $validated['customer_bird_weight'];
            $modeWeight     = (float) $validated['mode_weight'];
            $profitMargin   = (float) ($validated['profit_margin'] ?? SystemVariable::getValue('profit_margin', 20));

            if ($modeWeight <= 0) {
                return response()->json(['error' => 'Mode weight must be greater than zero.'], 422);
            }

            // Derive cost basis on-demand from raw records so the calculator
            // always matches the batch detail page.
            $avgCost = (float) $batch->getCostPerBird();

            if ($avgCost <= 0) {
                return response()->json([
                    'error' => 'The selected batch has no cost data yet. Please add feed, expenses, or inventory consumption first.'
                ], 422);
            }

            if ($batch->remaining_flock <= 0) {
                return response()->json([
                    'error' => 'The selected batch has no birds. Cannot calculate price.'
                ], 422);
            }

            // cost_scaled = (customer_weight / mode_weight) × avg_cost_per_bird
            $costScaled = ($customerWeight / $modeWeight) * $avgCost;

            // selling_price_per_bird = cost_scaled × (1 + profit_margin / 100)
            $sellingPricePerBird = $costScaled * (1 + $profitMargin / 100);

            $dressPercentage = (float) SystemVariable::getValue('dress_percentage', 75);
            $dressedWeight   = $customerWeight * ($dressPercentage / 100);

            $sellingPricePerKg = $dressedWeight > 0
                ? $sellingPricePerBird / $dressedWeight
                : 0;

            $sellingPricePerCarton = $sellingPricePerKg * 10;

            return response()->json([
                'success'                   => true,
                'batch_id'                  => $batch->id,
                'batch_name'                => $batch->batch_id . ' - ' . $batch->name,
                'customer_bird_weight'      => round($customerWeight, 3),
                'mode_weight'               => round($modeWeight, 3),
                'current_avg_cost'          => round($avgCost, 2),
                'cost_scaled'               => round($costScaled, 2),
                'profit_margin'             => round($profitMargin, 1),
                'selling_price_per_bird'    => round($sellingPricePerBird, 2),
                'selling_price_per_kg'      => round($sellingPricePerKg, 2),
                'selling_price_per_carton'  => round($sellingPricePerCarton, 2),
                'dress_percentage'          => $dressPercentage,
            ]);
        } catch (\Exception $e) {
            Log::error('Price calculator failed: ' . $e->getMessage());
            return response()->json(['error' => 'Calculation failed: ' . $e->getMessage()], 500);
        }
    }
}