<?php

namespace App\Observers\Poultry;

use App\Models\Poultry\FeedRecord;
use App\Models\Poultry\InventoryConsumption;
use App\Models\Poultry\InventoryItem;

class FeedRecordObserver
{
    /**
     * Handle the FeedRecord "created" event.
     */
    public function created(FeedRecord $feedRecord): void
    {
        if ($feedRecord->inventory_item_id) {
            $item = $feedRecord->inventoryItem;
            if ($item) {
                // Create inventory consumption
                InventoryConsumption::create([
                    'inventory_item_id' => $feedRecord->inventory_item_id,
                    'poultry_batch_id' => $feedRecord->poultry_batch_id,
                    'quantity_used' => $feedRecord->feed_used,
                    'date' => $feedRecord->date,
                    'recorded_by_id' => $feedRecord->recorded_by_id,
                    'source_type' => 'feed',
                    'source_id' => $feedRecord->id,
                    'unit_cost_at_time' => $item->cost_per_unit,
                    'total_cost' => $feedRecord->feed_used * $item->cost_per_unit,
                ]);
            }
        }
    }

    /**
     * Handle the FeedRecord "deleted" event.
     * Restore inventory stock and delete consumption record.
     */
    public function deleted(FeedRecord $feedRecord): void
    {
        if ($feedRecord->inventory_item_id) {
            // Restore inventory stock
            $item = InventoryItem::find($feedRecord->inventory_item_id);
            if ($item) {
                $item->quantity_in_stock += $feedRecord->feed_used;
                $item->quantity_used -= $feedRecord->feed_used;
                $item->save();
            }

            // Delete the consumption record
            InventoryConsumption::where('source_type', 'feed')
                ->where('source_id', $feedRecord->id)
                ->delete();
        }
    }
}