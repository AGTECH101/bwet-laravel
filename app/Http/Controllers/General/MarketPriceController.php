<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\MarketPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class MarketPriceController extends Controller
{
    /**
     * Display a listing of market prices.
     */
    public function index()
    {
        Gate::authorize('manage-market-prices');

        $currentPrice = MarketPrice::getCurrentPrices();
        $priceHistory = MarketPrice::getPriceHistory(30);

        return view('general.system.market-prices.index', compact('currentPrice', 'priceHistory'));
    }

    /**
     * Store a newly created market price.
     */
    public function store(Request $request)
    {
        Gate::authorize('manage-market-prices');

        $request->validate([
            'price_per_bird' => 'required|numeric|min:0.01',
            'price_per_kg' => 'required|numeric|min:0.01',
            'price_per_carton' => 'required|numeric|min:0.01',
            'effective_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        // If activating this price, deactivate others
        if ($request->has('is_active')) {
            MarketPrice::where('is_active', true)->update(['is_active' => false]);
        }

        $price = MarketPrice::create([
            'price_per_bird' => $request->price_per_bird,
            'price_per_kg' => $request->price_per_kg,
            'price_per_carton' => $request->price_per_carton,
            'effective_date' => $request->effective_date,
            'notes' => $request->notes,
            'is_active' => $request->has('is_active'),
            'set_by_id' => auth()->id(),
        ]);

        return redirect()->route('system.market-prices.index')
            ->with('success', 'Market price created successfully.');
    }
}