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

        return view('sectors.poultry.forms.inventory-consumption', compact('inventoryItems', 'batches', 'selectedItem'))->with('isWaste', false);
    }

    public function createWaste(Request $request)
    {
        Gate::authorize('create', InventoryConsumption::class);

        $inventoryItems = InventoryItem::where('is_active', true)->get();
        $batches = Batch::where('status', 'active')->get();

        return view('sectors.poultry.forms.inventory-consumption', compact('inventoryItems', 'batches'))->with('isWaste', true);
    }

    /**
     * Store a newly created consumption.
     */
    public function store(InventoryConsumptionRequest $request)
    {
        Gate::authorize('create', InventoryConsumption::class);

        $data = $request->validated();
        $item = InventoryItem::findOrFail($data['inventory_item_id']);

        if ((float) $data['quantity_used'] > (float) $item->quantity_in_stock) {
            return back()->withInput()->withErrors([
                'quantity_used' => 'Usage cannot exceed the remaining inventory quantity left (available: ' . number_format((float) $item->quantity_in_stock, 3) . ' ' . $item->unit . ').'
            ]);
        }

        $data['recorded_by_id'] = auth()->id();
        $data['source_type'] = 'manual';
        $data['unit_cost_at_time'] = $item->cost_per_unit;
        $data['total_cost'] = (float) $data['quantity_used'] * (float) $item->cost_per_unit;

        InventoryConsumption::create($data);

        $item->quantity_in_stock -= $data['quantity_used'];
        $item->quantity_used += $data['quantity_used'];
        $item->save();

        if (! empty($data['poultry_batch_id'])) {
            $batch = Batch::find($data['poultry_batch_id']);
            $batch?->updateCachedMetrics();
        }

        return redirect()->route('poultry.inventory.show', ['inventory' => $item->id])
            ->with('success', 'Inventory consumption recorded and batch cost updated.');
    }

    public function wasteIndex()
    {
        Gate::authorize('viewAny', InventoryConsumption::class);

        $wasteRecords = InventoryConsumption::with(['inventoryItem', 'batch', 'recordedBy'])
            ->where('source_type', 'waste')
            ->latest('date')
            ->paginate(20);

        return view('sectors.poultry.inventory.waste', compact('wasteRecords'));
    }

    public function storeWaste(Request $request)
    {
        Gate::authorize('create', InventoryConsumption::class);

        $validated = $request->validate([
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'poultry_batch_id' => ['nullable', 'exists:poultry_batches,id'],
            'quantity_used' => ['required', 'numeric', 'min:0.001'],
            'date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['required', 'string', 'min:20', 'max:1500'],
        ]);

        $item = InventoryItem::findOrFail($validated['inventory_item_id']);
        if ((float) $validated['quantity_used'] > (float) $item->quantity_in_stock) {
            return back()->withInput()->withErrors(['quantity_used' => 'Waste quantity cannot exceed the current stock on hand.']);
        }

        $validated['recorded_by_id'] = auth()->id();
        $validated['source_type'] = 'waste';
        $validated['unit_cost_at_time'] = $item->cost_per_unit;
        $validated['total_cost'] = 0;
        $validated['reason'] = $validated['reason'];
        $validated['notes'] = $validated['notes'];

        InventoryConsumption::create($validated);

        $item->quantity_in_stock -= $validated['quantity_used'];
        $item->quantity_used += $validated['quantity_used'];
        $item->save();

        return redirect()->route('poultry.inventory.index')
            ->with('success', 'Inventory waste was recorded successfully. No batch cost was inflated.');
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

        return redirect()->route('poultry.inventory.show', ['inventory' => $itemId])
            ->with('success', 'Consumption record deleted.');
    }
}
