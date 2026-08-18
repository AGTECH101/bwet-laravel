<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\SystemVariable;
use Illuminate\Http\Request;

class PriceCalculatorController extends Controller
{
    public function index()
    {
        $profitMargin = SystemVariable::getValue('profit_margin', 20);
        $dressPercentage = SystemVariable::getValue('dress_percentage', 75);
        return view('general.price-calculator', compact('profitMargin', 'dressPercentage'));
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'cost_per_bird' => 'required|numeric|min:0',
            'dressed_weight' => 'required|numeric|min:0.001',
            'target_margin' => 'required|numeric|min:0',
        ]);

        $costPerBird = (float) $request->cost_per_bird;
        $dressedWeight = (float) $request->dressed_weight;
        $targetMargin = (float) $request->target_margin;

        $costPerKg = $dressedWeight > 0 ? $costPerBird / $dressedWeight : 0;
        $minimumPrice = $costPerKg;
        $suggestedPricePerKg = $costPerKg * (1 + ($targetMargin / 100));

        return response()->json([
            'suggested_price_per_kg' => round($suggestedPricePerKg, 2),
            'minimum_price_per_kg' => round($minimumPrice, 2),
            'cost_per_kg' => round($costPerKg, 2),
            'profit_margin' => $targetMargin,
        ]);
    }
}