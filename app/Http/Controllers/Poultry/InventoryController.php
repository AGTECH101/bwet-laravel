<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\InventoryItemRequest;
use App\Models\Poultry\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $query = InventoryItem::query();

        // If show_killed is true, show only killed items; else show only active items
        if ($request->boolean('show_killed')) {
            $query->where('is_active', false);
        } else {
            $query->where('is_active', true);
        }

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

    public function create()
    {
        Gate::authorize('create', InventoryItem::class);
        return view('sectors.poultry.forms.inventory-item');
    }

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

    // FIX: parameter name matches route {inventory}
    public function show(InventoryItem $inventory)
    {
        Gate::authorize('view', $inventory);

        $consumptionHistory = $inventory->consumptions()
            ->with('batch', 'recordedBy')
            ->latest('date')
            ->limit(20)
            ->get();

        return view('sectors.poultry.inventory.show', compact('inventory', 'consumptionHistory'));
    }

    // FIX: parameter name matches route {inventory}
    public function edit(InventoryItem $inventory)
    {
        Gate::authorize('update', $inventory);

        if (!$inventory->is_active) {
            return redirect()->route('poultry.inventory.show', $inventory)
                ->with('error', 'Cannot edit a killed (deactivated) inventory item.');
        }

        return view('sectors.poultry.forms.inventory-item', compact('inventory'));
    }

    // FIX: parameter name matches route {inventory}
    public function update(InventoryItemRequest $request, InventoryItem $inventory)
    {
        Gate::authorize('update', $inventory);

        if (!$inventory->is_active) {
            return redirect()->route('poultry.inventory.show', $inventory)
                ->with('error', 'Cannot update a killed (deactivated) inventory item.');
        }

        $inventory->update($request->validated());

        return redirect()->route('poultry.inventory.show', $inventory)
            ->with('success', 'Inventory item updated successfully.');
    }

    // FIX: parameter name matches route {inventory}
    public function destroy(InventoryItem $inventory)
    {
        Gate::authorize('delete', $inventory);
        $inventory->delete();
        return redirect()->route('poultry.inventory.index')
            ->with('success', 'Inventory item deleted.');
    }

    // This route uses {item} (kill and recalculate use {item})
    public function kill(Request $request, InventoryItem $item)
    {
        Gate::authorize('update', $item);

        if (!$item->is_active) {
            return redirect()->route('poultry.inventory.show', $item)
                ->with('error', 'This item is already killed.');
        }

        $item->is_active = false;
        $item->status = 'killed';
        $item->killed_by_id = auth()->id();
        $item->killed_at = now();
        $item->killed_reason = $request->input('reason');
        $item->save();

        return redirect()->route('poultry.inventory.index')
            ->with('success', 'Inventory item killed (deactivated) successfully.');
    }

    // This route uses {item}
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