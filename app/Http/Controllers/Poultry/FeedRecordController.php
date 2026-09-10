<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\FeedRecordRequest;
use App\Models\Poultry\Batch;
use App\Models\Poultry\FeedRecord;
use App\Models\Poultry\InventoryItem;
use App\Models\Poultry\InventoryConsumption;
use App\Services\Poultry\BatchRecalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class FeedRecordController extends Controller
{
    /**
     * Display feed records for a batch.
     */
    public function index(Batch $batch)
    {
        Gate::authorize('view', $batch);
        $records = $batch->feedRecords()->with('recordedBy', 'inventoryItem')->latest('date')->paginate(20);
        return view('sectors.poultry.feed-records.index', compact('batch', 'records'));
    }

    /**
     * Show the create form (used by Form Hub AND resource route).
     */
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

    /**
     * Store a new feed record.
     * - Deducts inventory stock
     * - Creates inventory consumption entry
     * - Recalculates batch metrics
     */
    public function store(FeedRecordRequest $request)
    {
        Gate::authorize('create', FeedRecord::class);

        $data = $request->validated();

        $batch = Batch::findOrFail($data['poultry_batch_id']);
        if ($batch->status !== 'active') {
            return redirect()->back()->with('error', 'Cannot add records to a closed or completed batch.');
        }

        $item = InventoryItem::findOrFail($data['inventory_item_id']);

        // Validate stock availability
        if ($item->quantity_in_stock < $data['feed_used']) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Insufficient inventory stock. Available: ' . $item->quantity_in_stock . ' ' . $item->unit . '.');
        }

        $data['feed_cost_per_kg'] = $item->cost_per_unit;
        $data['total_feed_cost'] = $data['feed_used'] * $item->cost_per_unit;
        $data['feed_per_bird'] = 0;
        $data['recorded_by_id'] = auth()->id();

        DB::transaction(function () use ($data, $batch, $item) {
            $record = FeedRecord::create($data);

            // Deduct inventory stock
            $item->quantity_in_stock -= $record->feed_used;
            $item->quantity_used += $record->feed_used;
            $item->save();

            // Create consumption record
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

            BatchRecalculationService::recalculateAll($batch);
        });

        return redirect()->route('poultry.batches.show', $batch)
            ->with('success', 'Feed record saved and metrics recalculated.');
    }

    /**
     * Show the edit form.
     */
    public function edit(FeedRecord $feedRecord)
    {
        Gate::authorize('update', $feedRecord);

        $inventoryItems = InventoryItem::where('category', 'feed')
            ->where('is_active', true)
            ->get();

        return view('sectors.poultry.feed-records.edit', compact('feedRecord', 'inventoryItems'));
    }

    /**
     * Update a feed record.
     * Reverses old inventory + consumption, applies new, then recalculates.
     */
    public function update(FeedRecordRequest $request, FeedRecord $feedRecord)
    {
        Gate::authorize('update', $feedRecord);

        $data = $request->validated();
        $newItem = InventoryItem::findOrFail($data['inventory_item_id']);

        DB::transaction(function () use ($feedRecord, $data, $newItem) {
            $batch = $feedRecord->batch;

            // Restore old inventory stock
            $oldItem = $feedRecord->inventoryItem;
            if ($oldItem) {
                $oldItem->quantity_in_stock += $feedRecord->feed_used;
                $oldItem->quantity_used -= $feedRecord->feed_used;
                $oldItem->save();
            }

            // Delete old consumption
            InventoryConsumption::where('source_type', 'feed')
                ->where('source_id', $feedRecord->id)
                ->delete();

            // Update feed record with new data
            $data['feed_cost_per_kg'] = $newItem->cost_per_unit;
            $data['total_feed_cost'] = $data['feed_used'] * $newItem->cost_per_unit;
            $data['feed_per_bird'] = 0;
            $feedRecord->update($data);

            // Deduct new inventory stock
            $newItem->quantity_in_stock -= $feedRecord->feed_used;
            $newItem->quantity_used += $feedRecord->feed_used;
            $newItem->save();

            // Create new consumption record
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

            BatchRecalculationService::recalculateAll($batch);
        });

        return redirect()->route('poultry.batches.show', $feedRecord->batch)
            ->with('success', 'Feed record updated and metrics recalculated.');
    }

    /**
     * Delete a feed record.
     * Restores inventory stock, deletes consumption, recalculates.
     */
    public function destroy(FeedRecord $feedRecord)
    {
        Gate::authorize('delete', $feedRecord);

        DB::transaction(function () use ($feedRecord) {
            $batch = $feedRecord->batch;

            // Restore inventory stock
            $item = $feedRecord->inventoryItem;
            if ($item) {
                $item->quantity_in_stock += $feedRecord->feed_used;
                $item->quantity_used -= $feedRecord->feed_used;
                $item->save();
            }

            // Delete consumption record
            InventoryConsumption::where('source_type', 'feed')
                ->where('source_id', $feedRecord->id)
                ->delete();

            $feedRecord->delete();
            BatchRecalculationService::recalculateAll($batch);
        });

        return redirect()->back()->with('success', 'Feed record deleted, inventory restored, and metrics recalculated.');
    }
}