<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\FeedRecordRequest;
use App\Models\Poultry\Batch;
use App\Models\Poultry\FeedRecord;
use App\Models\Poultry\InventoryItem;
use App\Models\Poultry\InventoryConsumption;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

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
            ->where('status', 'active')
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

        // Check batch status
        $batch = Batch::findOrFail($data['poultry_batch_id']);
        if ($batch->status !== 'active') {
            return redirect()->back()->with('error', 'Cannot add records to a closed or completed batch.');
        }

        // Get inventory item and calculate costs
        $item = InventoryItem::findOrFail($data['inventory_item_id']);
        $data['feed_cost_per_kg'] = $item->cost_per_unit;
        $data['total_feed_cost'] = $data['feed_used'] * $item->cost_per_unit;
        $data['feed_per_bird'] = 0;
        $data['recorded_by_id'] = auth()->id();

        DB::transaction(function () use ($data, $batch, $item) {
            // Create the feed record
            $record = FeedRecord::create($data);

            // Update batch state: add cost
            $changes = [
                'count' => 0,
                'weight' => 0,
                'cost' => $record->total_feed_cost,
            ];
            $batch->updateState($changes, 'feed');

            // Also update total_feed_used for FCR calculation
            $batch->total_feed_used += $record->feed_used;
            $batch->save();

            // Update inventory stock (via observer or manually)
            // Using the observer ensures consistency, but we can also manually update.
            // The observer is triggered on FeedRecord creation.
            // We'll rely on the observer (registered in EventServiceProvider).
            // The observer will create InventoryConsumption and adjust stock.

            // After the observer runs, we still need to update FCR-related fields
            $batch->updateCachedMetrics(); // this will recalc FCR based on total_feed_used and weight_gain
        });

        return redirect()->route('poultry.batches.show', $batch)
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

        DB::transaction(function () use ($feedRecord, $data) {
            $batch = $feedRecord->batch;
            $oldFeedUsed = $feedRecord->feed_used;
            $oldTotalCost = $feedRecord->total_feed_cost;
            $newFeedUsed = $data['feed_used'];
            $newItem = InventoryItem::findOrFail($data['inventory_item_id']);
            $newTotalCost = $newFeedUsed * $newItem->cost_per_unit;

            // Remove old stock adjustment (via observer deletion)
            // The observer will handle this automatically when we delete and recreate.
            // But we need to reverse the old state changes first.
            $oldChanges = [
                'count' => 0,
                'weight' => 0,
                'cost' => -$oldTotalCost,
            ];
            $batch->updateState($oldChanges, 'feed');

            // Restore total_feed_used
            $batch->total_feed_used -= $oldFeedUsed;
            $batch->save();

            // Update the feed record
            $feedRecord->inventory_item_id = $data['inventory_item_id'];
            $feedRecord->feed_used = $newFeedUsed;
            $feedRecord->feed_cost_per_kg = $newItem->cost_per_unit;
            $feedRecord->total_feed_cost = $newTotalCost;
            $feedRecord->feed_per_bird = 0;
            $feedRecord->date = $data['date'];
            $feedRecord->save();

            // Apply new state changes
            $newChanges = [
                'count' => 0,
                'weight' => 0,
                'cost' => $newTotalCost,
            ];
            $batch->updateState($newChanges, 'feed');

            // Update total_feed_used
            $batch->total_feed_used += $newFeedUsed;
            $batch->save();

            // Update inventory (observer handles new consumption)
            // We need to manually create a new consumption because the observer only triggers on create.
            // But we can also rely on the observer if we delete and recreate. However, for simplicity,
            // we'll manually adjust stock here.

            // If the item changed, we need to adjust both old and new items.
            // This is complex; for now, we'll just delete the old consumption and create a new one.
            $oldConsumption = InventoryConsumption::where('source_type', 'feed')
                ->where('source_id', $feedRecord->id)
                ->first();
            if ($oldConsumption) {
                // Restore old item stock
                $oldItem = $feedRecord->inventoryItem;
                $oldItem->quantity_in_stock += $oldConsumption->quantity_used;
                $oldItem->quantity_used -= $oldConsumption->quantity_used;
                $oldItem->save();
                $oldConsumption->delete();
            }

            // Create new consumption
            InventoryConsumption::create([
                'inventory_item_id' => $newItem->id,
                'poultry_batch_id' => $batch->id,
                'quantity_used' => $newFeedUsed,
                'date' => $feedRecord->date,
                'recorded_by_id' => auth()->id(),
                'source_type' => 'feed',
                'source_id' => $feedRecord->id,
                'unit_cost_at_time' => $newItem->cost_per_unit,
                'total_cost' => $newTotalCost,
            ]);

            // Adjust new item stock
            $newItem->quantity_in_stock -= $newFeedUsed;
            $newItem->quantity_used += $newFeedUsed;
            $newItem->save();

            $batch->updateCachedMetrics();
        });

        return redirect()->route('poultry.batches.show', $feedRecord->batch)
            ->with('success', 'Feed record updated.');
    }

    public function destroy(FeedRecord $feedRecord)
    {
        Gate::authorize('delete', $feedRecord);

        DB::transaction(function () use ($feedRecord) {
            $batch = $feedRecord->batch;

            // Reverse state changes
            $changes = [
                'count' => 0,
                'weight' => 0,
                'cost' => -$feedRecord->total_feed_cost,
            ];
            $batch->updateState($changes, 'feed');

            // Update total_feed_used
            $batch->total_feed_used -= $feedRecord->feed_used;
            $batch->save();

            // The observer will handle stock restoration on deletion
            $feedRecord->delete();

            $batch->updateCachedMetrics();
        });

        return redirect()->back()->with('success', 'Feed record deleted.');
    }
}