<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\InventoryConsumptionRequest;
use App\Models\Poultry\Batch;
use App\Models\Poultry\InventoryConsumption;
use App\Models\Poultry\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InventoryConsumptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', InventoryConsumption::class);

        $query = InventoryConsumption::with('inventoryItem', 'batch', 'recordedBy');

        if ($request->has('inventory_item')) {
            $query->where('inventory_item_id', $request->inventory_item);
        }
        if ($request->has('batch')) {
            $query->where('poultry_batch_id', $request->batch);
        }

        $consumptions = $query->latest('date')->paginate(20);

        $inventoryItems = InventoryItem::where('is_active', true)->get();
        $batches = Batch::where('status', 'active')->get();

        return view('sectors.poultry.inventory-consumptions.index', compact('consumptions', 'inventoryItems', 'batches'));
    }

    /**
     * Show the form for creating a new consumption.
     */
    public function create(Request $request)
    {
        Gate::authorize('create', InventoryConsumption::class);

        $inventoryItems = InventoryItem::where('is_active', true)->get();
        $batches = Batch::where('status', 'active')->get();

        $selectedItem = null;
        if ($request->has('inventory_item')) {
            $selectedItem = InventoryItem::find($request->inventory_item);
        }

        return view('sectors.poultry.forms.inventory-consumption', compact('inventoryItems', 'batches', 'selectedItem'));
    }

    /**
     * Store a newly created consumption.
     */
    public function store(InventoryConsumptionRequest $request)
    {
        Gate::authorize('create', InventoryConsumption::class);

        $data = $request->validated();
        $data['recorded_by_id'] = auth()->id();

        $consumption = InventoryConsumption::create($data);

        $item = InventoryItem::find($data['inventory_item_id']);
        $item->quantity_in_stock -= $data['quantity_used'];
        $item->quantity_used += $data['quantity_used'];
        $item->save();

        if ($data['poultry_batch_id'] ?? false) {
            $batch = Batch::find($data['poultry_batch_id']);
            $batch?->updateCachedMetrics();
        }

        return redirect()->route('poultry.inventory.show', $data['inventory_item_id'])
            ->with('success', 'Inventory consumption recorded.');
    }

    /**
     * Remove the specified consumption.
     */
    public function destroy(InventoryConsumption $consumption)
    {
        Gate::authorize('delete', $consumption);

        $itemId = $consumption->inventory_item_id;
        $batchId = $consumption->poultry_batch_id;

        $item = InventoryItem::find($itemId);
        if ($item) {
            $item->quantity_in_stock += $consumption->quantity_used;
            $item->quantity_used -= $consumption->quantity_used;
            $item->save();
        }

        $consumption->delete();

        if ($batchId) {
            $batch = Batch::find($batchId);
            $batch?->updateCachedMetrics();
        }

        return redirect()->route('poultry.inventory.show', $itemId)
            ->with('success', 'Consumption record deleted.');
    }
}
