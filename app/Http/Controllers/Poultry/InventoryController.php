<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\InventoryItemRequest;
use App\Models\Poultry\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $query = InventoryItem::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'low') {
                $query->whereColumn('quantity_in_stock', '<=', 'minimum_quantity');
            }

            if ($request->status === 'out') {
                $query->where('quantity_in_stock', '<=', 0);
            }
        }

        $items = $query->orderBy('name')->paginate(20);
        $totalValue = $items->sum(fn ($item) => (float) $item->quantity_in_stock * (float) $item->cost_per_unit);

        return view('sectors.poultry.inventory.index', compact('items', 'totalValue'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', InventoryItem::class);

        return view('sectors.poultry.forms.inventory-item');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(InventoryItemRequest $request)
    {
        Gate::authorize('create', InventoryItem::class);

        $validated = $request->validated();
        $validated['created_by_id'] = auth()->id();
        $validated['status'] = 'active';

        $item = InventoryItem::create($validated);

        return redirect()->route('poultry.inventory.show', $item)
            ->with('success', 'Inventory item created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryItem $item)
    {
        Gate::authorize('view', $item);

        $consumptionHistory = $item->consumptions()
            ->with('batch', 'recordedBy')
            ->latest('date')
            ->limit(20)
            ->get();

        return view('sectors.poultry.inventory.show', compact('item', 'consumptionHistory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryItem $item)
    {
        Gate::authorize('update', $item);

        return view('sectors.poultry.forms.inventory-item', compact('item'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InventoryItemRequest $request, InventoryItem $item)
    {
        Gate::authorize('update', $item);

        $item->update($request->validated());

        return redirect()->route('poultry.inventory.show', $item)
            ->with('success', 'Inventory item updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryItem $item)
    {
        Gate::authorize('delete', $item);

        $item->delete();

        return redirect()->route('poultry.inventory.index')
            ->with('success', 'Inventory item deleted.');
    }

    /**
     * Mark an inventory item as killed.
     */
    public function kill(Request $request, InventoryItem $item)
    {
        Gate::authorize('update', $item);

        $item->status = 'killed';
        $item->is_active = false;
        $item->killed_by_id = auth()->id();
        $item->killed_at = now();
        $item->killed_reason = $request->input('reason');
        $item->save();

        return redirect()->route('poultry.inventory.show', $item)
            ->with('success', 'Inventory item marked as killed.');
    }

    /**
     * Recalculate historical costs for the item.
     */
    public function recalculateCosts(InventoryItem $item)
    {
        Gate::authorize('update', $item);

        foreach ($item->consumptions as $consumption) {
            $consumption->unit_cost_at_time = $item->cost_per_unit;
            $consumption->total_cost = $consumption->quantity_used * $item->cost_per_unit;
            $consumption->save();
        }

        return redirect()->back()->with('success', 'Historical inventory costs recalculated.');
    }
}