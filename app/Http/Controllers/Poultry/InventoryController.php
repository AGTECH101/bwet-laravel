<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\InventoryItemRequest;
use App\Models\Poultry\InventoryItem;
use App\Models\Poultry\Batch;
use App\Services\Poultry\BatchRecalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $query = InventoryItem::query();

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

    public function edit(InventoryItem $item)
    {
        Gate::authorize('update', $item);

        if (!$item->is_active) {
            return redirect()->route('poultry.inventory.show', $item)
                ->with('error', 'Cannot edit a killed (deactivated) inventory item.');
        }

        return view('sectors.poultry.forms.inventory-item', compact('item'));
    }

    public function update(InventoryItemRequest $request, InventoryItem $item)
    {
        Gate::authorize('update', $item);

        if (!$item->is_active) {
            return redirect()->route('poultry.inventory.show', $item)
                ->with('error', 'Cannot update a killed (deactivated) inventory item.');
        }

        DB::transaction(function () use ($item, $request) {
            $item->update($request->validated());

            // Recalculate all batches that consumed this item
            $batchIds = $item->consumptions()->pluck('poultry_batch_id')->filter()->unique();
            foreach ($batchIds as $batchId) {
                $batch = Batch::find($batchId);
                if ($batch) {
                    BatchRecalculationService::recalculateAll($batch);
                }
            }
        });

        return redirect()->route('poultry.inventory.show', $item)
            ->with('success', 'Inventory item updated successfully.');
    }

    public function destroy(InventoryItem $item)
    {
        Gate::authorize('delete', $item);
        $item->delete();
        return redirect()->route('poultry.inventory.index')
            ->with('success', 'Inventory item deleted.');
    }

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

    public function recalculateCosts(InventoryItem $item)
    {
        Gate::authorize('update', $item);

        DB::transaction(function () use ($item) {
            foreach ($item->consumptions as $consumption) {
                $consumption->unit_cost_at_time = $item->cost_per_unit;
                $consumption->total_cost = $consumption->quantity_used * $item->cost_per_unit;
                $consumption->save();
            }

            // Recalculate batches that consumed this item
            $batchIds = $item->consumptions()->pluck('poultry_batch_id')->filter()->unique();
            foreach ($batchIds as $batchId) {
                $batch = Batch::find($batchId);
                if ($batch) {
                    BatchRecalculationService::recalculateAll($batch);
                }
            }
        });

        return redirect()->back()->with('success', 'Historical inventory costs recalculated.');
    }
}