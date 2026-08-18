<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\FeedRecordRequest;
use App\Models\Poultry\Batch;
use App\Models\Poultry\FeedRecord;
use App\Models\Poultry\InventoryItem;
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

        $feedItems = InventoryItem::where('category', 'feed')->where('is_active', true)->get();
        $inventoryItems = $feedItems;
        return view('sectors.poultry.feed-records.create', compact('batch', 'batches', 'inventoryItems', 'feedItems'));
    }

    public function store(FeedRecordRequest $request)
    {
        Gate::authorize('create', FeedRecord::class);

        $data = $request->validated();
        $data['recorded_by_id'] = auth()->id();

        // Auto-calculate costs from inventory item
        $item = InventoryItem::findOrFail($data['inventory_item_id']);
        $data['feed_cost_per_kg'] = $item->cost_per_unit;
        $data['total_feed_cost'] = $data['feed_used'] * $item->cost_per_unit;
        $data['feed_per_bird'] = 0; // will be calculated in observer or on save

        $record = FeedRecord::create($data);

        $item = InventoryItem::findOrFail($data['inventory_item_id']);
        $item->quantity_in_stock = max(0, (float) $item->quantity_in_stock - (float) $data['feed_used']);
        $item->quantity_used = (float) $item->quantity_used + (float) $data['feed_used'];
        $item->save();

        $record->batch?->updateCachedMetrics();

        return redirect()->route('poultry.batches.show', $record->batch)
            ->with('success', 'Feed record saved. Inventory stock and batch cost were updated.');
    }

    public function edit(FeedRecord $feedRecord)
    {
        Gate::authorize('update', $feedRecord);
        $inventoryItems = InventoryItem::where('category', 'feed')->where('is_active', true)->get();
        return view('sectors.poultry.feed-records.edit', compact('feedRecord', 'inventoryItems'));
    }

    public function update(FeedRecordRequest $request, FeedRecord $feedRecord)
    {
        Gate::authorize('update', $feedRecord);

        $data = $request->validated();
        // Recalculate costs
        $item = InventoryItem::findOrFail($data['inventory_item_id']);
        $data['feed_cost_per_kg'] = $item->cost_per_unit;
        $data['total_feed_cost'] = $data['feed_used'] * $item->cost_per_unit;

        $feedRecord->update($data);
        $feedRecord->batch->updateCachedMetrics();

        return redirect()->route('poultry.batches.show', $feedRecord->batch)
            ->with('success', 'Feed record updated.');
    }

    public function destroy(FeedRecord $feedRecord)
    {
        Gate::authorize('delete', $feedRecord);

        $batch = $feedRecord->batch;
        $feedRecord->delete();
        $batch->updateCachedMetrics();

        return redirect()->back()->with('success', 'Feed record deleted.');
    }
}