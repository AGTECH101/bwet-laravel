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

        $batch = Batch::findOrFail($data['poultry_batch_id']);
        if ($batch->status !== 'active') {
            return redirect()->back()->with('error', 'Cannot add records to a closed or completed batch.');
        }

        $item = InventoryItem::findOrFail($data['inventory_item_id']);
        $data['feed_cost_per_kg'] = $item->cost_per_unit;
        $data['total_feed_cost'] = $data['feed_used'] * $item->cost_per_unit;
        $data['feed_per_bird'] = 0;
        $data['recorded_by_id'] = auth()->id();

        DB::transaction(function () use ($data, $batch, $item) {
            $record = FeedRecord::create($data);

            // Update batch state: add cost, increase feed used
            $batch->current_cost += $record->total_feed_cost;
            $batch->current_average_cost = $batch->current_count > 0
                ? $batch->current_cost / $batch->current_count
                : 0;
            $batch->total_feed_used += $record->feed_used;
            $batch->save();

            // Update inventory stock
            $item->quantity_in_stock -= $record->feed_used;
            $item->quantity_used += $record->feed_used;
            $item->save();

            // Create inventory consumption (also ensures cost is logged)
            InventoryConsumption::create([
                'inventory_item_id' => $item->id,
                'poultry_batch_id' => $batch->id,
                'quantity_used' => $record->feed_used,
                'date' => $record->date,
                'recorded_by_id' => auth()->id(),
                'source_type' => 'feed',
                'source_id' => $record->id,
                'unit_cost_at_time' => $item->cost_per_unit,
                'total_cost' => $record->total_feed_cost,
            ]);

            $batch->updateCachedMetrics();
        });

        return redirect()->route('poultry.batches.show', $batch)
            ->with('success', 'Feed record saved.');
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

        DB::transaction(function () use ($feedRecord, $data, $newItem) {
            $batch = $feedRecord->batch;

            // Reverse old feed record from state
            $oldFeedUsed = $feedRecord->feed_used;
            $oldTotalCost = $feedRecord->total_feed_cost;

            $batch->current_cost -= $oldTotalCost;
            $batch->total_feed_used -= $oldFeedUsed;
            $batch->current_average_cost = $batch->current_count > 0
                ? $batch->current_cost / $batch->current_count
                : 0;

            // Restore old inventory stock
            $oldItem = $feedRecord->inventoryItem;
            if ($oldItem) {
                $oldItem->quantity_in_stock += $oldFeedUsed;
                $oldItem->quantity_used -= $oldFeedUsed;
                $oldItem->save();
            }

            // Delete old consumption
            InventoryConsumption::where('source_type', 'feed')
                ->where('source_id', $feedRecord->id)
                ->delete();

            // Update feed record
            $feedRecord->fill($data);
            $feedRecord->feed_cost_per_kg = $newItem->cost_per_unit;
            $feedRecord->total_feed_cost = $data['feed_used'] * $newItem->cost_per_unit;
            $feedRecord->feed_per_bird = 0;
            $feedRecord->save();

            // Apply new values
            $batch->current_cost += $feedRecord->total_feed_cost;
            $batch->total_feed_used += $feedRecord->feed_used;
            $batch->current_average_cost = $batch->current_count > 0
                ? $batch->current_cost / $batch->current_count
                : 0;

            // Update new inventory stock
            $newItem->quantity_in_stock -= $feedRecord->feed_used;
            $newItem->quantity_used += $feedRecord->feed_used;
            $newItem->save();

            // Create new consumption
            InventoryConsumption::create([
                'inventory_item_id' => $newItem->id,
                'poultry_batch_id' => $batch->id,
                'quantity_used' => $feedRecord->feed_used,
                'date' => $feedRecord->date,
                'recorded_by_id' => auth()->id(),
                'source_type' => 'feed',
                'source_id' => $feedRecord->id,
                'unit_cost_at_time' => $newItem->cost_per_unit,
                'total_cost' => $feedRecord->total_feed_cost,
            ]);

            $batch->save();
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

            // Reverse the state changes
            $batch->current_cost -= $feedRecord->total_feed_cost;
            $batch->total_feed_used -= $feedRecord->feed_used;
            $batch->current_average_cost = $batch->current_count > 0
                ? $batch->current_cost / $batch->current_count
                : 0;

            // Restore inventory stock
            $item = $feedRecord->inventoryItem;
            if ($item) {
                $item->quantity_in_stock += $feedRecord->feed_used;
                $item->quantity_used -= $feedRecord->feed_used;
                $item->save();
            }

            // Delete consumption
            InventoryConsumption::where('source_type', 'feed')
                ->where('source_id', $feedRecord->id)
                ->delete();

            $feedRecord->delete();
            $batch->save();
            $batch->updateCachedMetrics();
        });

        return redirect()->back()->with('success', 'Feed record deleted.');
    }
}