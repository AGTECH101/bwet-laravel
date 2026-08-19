<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\FeedRecordRequest;
use App\Models\Poultry\Batch;
use App\Models\Poultry\FeedRecord;
use App\Models\Poultry\InventoryItem;
use App\Models\Poultry\InventoryConsumption;
use Illuminate\Support\Facades\Gate;

class FeedRecordController extends Controller
{
    public function index(Batch $batch)
    {
        Gate::authorize('view', $batch);
        $records = $batch->feedRecords()->with('recordedBy', 'inventoryItem')->latest('date')->paginate(20);
        return view('sectors.poultry.feed-records.index', compact('batch', 'records'));
    }

    public function create(?Batch $batch = null)
    {
        Gate::authorize('create', FeedRecord::class);

        $batches = Batch::query()
            ->orderBy('start_date', 'desc')
            ->get();

        $feedItems = InventoryItem::where('category', 'feed')
            ->where('is_active', true)
            ->get();

        return view('sectors.poultry.forms.feed-record', compact('batch', 'batches', 'feedItems'));
    }

    public function store(FeedRecordRequest $request)
    {
        Gate::authorize('create', FeedRecord::class);

        $data = $request->validated();
        $data['recorded_by_id'] = auth()->id();

        $item = InventoryItem::findOrFail($data['inventory_item_id']);
        $data['feed_cost_per_kg'] = $item->cost_per_unit;
        $data['total_feed_cost'] = $data['feed_used'] * $item->cost_per_unit;
        $data['feed_per_bird'] = 0;

        $record = FeedRecord::create($data);

        // Manually create inventory consumption and update stock
        $consumption = InventoryConsumption::create([
            'inventory_item_id' => $item->id,
            'poultry_batch_id' => $record->poultry_batch_id,
            'quantity_used' => $data['feed_used'],
            'date' => $record->date,
            'recorded_by_id' => auth()->id(),
            'source_type' => 'feed',
            'source_id' => $record->id,
            'unit_cost_at_time' => $item->cost_per_unit,
            'total_cost' => $data['feed_used'] * $item->cost_per_unit,
        ]);

        // Update inventory stock
        $item->quantity_in_stock -= $data['feed_used'];
        $item->quantity_used += $data['feed_used'];
        $item->save();

        // Update batch metrics
        $record->batch->updateCachedMetrics();

        return redirect()->route('poultry.batches.show', $record->batch)
            ->with('success', 'Feed record saved. Inventory stock and batch cost were updated.');
    }

    public function edit(FeedRecord $feedRecord)
    {
        Gate::authorize('update', $feedRecord);

        $inventoryItems = InventoryItem::where('category', 'feed')
            ->where('is_active', true)
            ->get();

        return view('sectors.poultry.feed-records.edit', compact('feedRecord', 'inventoryItems'));
    }

    public function update(FeedRecordRequest $request, FeedRecord $feedRecord)
    {
        Gate::authorize('update', $feedRecord);

        $data = $request->validated();
        $newItem = InventoryItem::findOrFail($data['inventory_item_id']);

        // Remove old consumption and restore stock
        $oldConsumption = InventoryConsumption::where('source_type', 'feed')
            ->where('source_id', $feedRecord->id)
            ->first();

        if ($oldConsumption) {
            $oldItem = $feedRecord->inventoryItem;
            $oldItem->quantity_in_stock += $oldConsumption->quantity_used;
            $oldItem->quantity_used -= $oldConsumption->quantity_used;
            $oldItem->save();
            $oldConsumption->delete();
        }

        // Update record with new data
        $data['feed_cost_per_kg'] = $newItem->cost_per_unit;
        $data['total_feed_cost'] = $data['feed_used'] * $newItem->cost_per_unit;
        $data['feed_per_bird'] = 0;

        $feedRecord->update($data);

        // Create new consumption and update stock
        InventoryConsumption::create([
            'inventory_item_id' => $newItem->id,
            'poultry_batch_id' => $feedRecord->poultry_batch_id,
            'quantity_used' => $data['feed_used'],
            'date' => $feedRecord->date,
            'recorded_by_id' => auth()->id(),
            'source_type' => 'feed',
            'source_id' => $feedRecord->id,
            'unit_cost_at_time' => $newItem->cost_per_unit,
            'total_cost' => $data['feed_used'] * $newItem->cost_per_unit,
        ]);

        $newItem->quantity_in_stock -= $data['feed_used'];
        $newItem->quantity_used += $data['feed_used'];
        $newItem->save();

        $feedRecord->batch->updateCachedMetrics();

        return redirect()->route('poultry.batches.show', $feedRecord->batch)
            ->with('success', 'Feed record updated.');
    }

    public function destroy(FeedRecord $feedRecord)
    {
        Gate::authorize('delete', $feedRecord);

        $batch = $feedRecord->batch;

        // Restore stock from associated consumption
        $consumption = InventoryConsumption::where('source_type', 'feed')
            ->where('source_id', $feedRecord->id)
            ->first();

        if ($consumption) {
            $item = $consumption->inventoryItem;
            $item->quantity_in_stock += $consumption->quantity_used;
            $item->quantity_used -= $consumption->quantity_used;
            $item->save();
            $consumption->delete();
        }

        $feedRecord->delete();
        $batch->updateCachedMetrics();

        return redirect()->back()->with('success', 'Feed record deleted.');
    }
}