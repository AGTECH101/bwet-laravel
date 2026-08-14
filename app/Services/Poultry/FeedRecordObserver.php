<?php

namespace App\Observers\Poultry;

use App\Models\Poultry\FeedRecord;
use App\Models\Poultry\InventoryConsumption;

class FeedRecordObserver
{
    public function created(FeedRecord $feedRecord)
    {
        if ($feedRecord->inventory_item_id) {
            InventoryConsumption::create([
                'inventory_item_id' => $feedRecord->inventory_item_id,
                'poultry_batch_id' => $feedRecord->poultry_batch_id,
                'quantity_used' => $feedRecord->feed_used,
                'date' => $feedRecord->date,
                'recorded_by_id' => $feedRecord->recorded_by_id,
                'source_type' => 'feed',
                'source_id' => $feedRecord->id,
                'unit_cost_at_time' => $feedRecord->inventoryItem->cost_per_unit,
                'total_cost' => $feedRecord->feed_used * $feedRecord->inventoryItem->cost_per_unit,
            ]);
        }
    }

    public function deleted(FeedRecord $feedRecord)
    {
        if ($feedRecord->inventory_item_id) {
            InventoryConsumption::where('source_type', 'feed')
                ->where('source_id', $feedRecord->id)
                ->delete();
        }
    }
}