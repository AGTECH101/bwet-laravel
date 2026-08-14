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

        $costPerBird = $request->cost_per_bird;
        $dressedWeight = $request->dressed_weight;
        $targetMargin = $request->target_margin;

        $suggestedPricePerKg = ($costPerBird * (1 + $targetMargin / 100)) / $dressedWeight;
        $minimumPrice = ($costPerBird / $dressedWeight); // break-even

        return response()->json([
            'suggested_price_per_kg' => round($suggestedPricePerKg, 2),
            'minimum_price_per_kg' => round($minimumPrice, 2),
            'profit_margin' => $targetMargin,
        ]);
    }
}