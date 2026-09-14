<?php

namespace App\Observers\Poultry;

use App\Models\Poultry\FeedRecord;
use App\Models\Poultry\InventoryConsumption;

class FeedRecordObserver
{
    /**
     * On create, spin up the linked inventory consumption row.
     * The InventoryConsumptionObserver performs the actual stock deduction.
     */
    public function created(FeedRecord $feedRecord): void
    {
        if (! $feedRecord->inventory_item_id) {
            return;
        }

        $item = $feedRecord->inventoryItem;
        if (! $item) {
            return;
        }

        InventoryConsumption::create([
            'inventory_item_id' => $feedRecord->inventory_item_id,
            'poultry_batch_id'  => $feedRecord->poultry_batch_id,
            'quantity_used'     => $feedRecord->feed_used,
            'date'              => $feedRecord->date,
            'recorded_by_id'    => $feedRecord->recorded_by_id,
            'source_type'       => 'feed',
            'source_id'         => $feedRecord->id,
            'unit_cost_at_time' => $item->cost_per_unit,
            'total_cost'        => $feedRecord->feed_used * $item->cost_per_unit,
        ]);
    }

    /**
     * On update, rebuild the linked consumption row.
     *
     * Deleting the old row triggers InventoryConsumptionObserver::deleted
     * which restores the old stock. Creating the new row triggers
     * InventoryConsumptionObserver::created which deducts the new amount.
     * Net result is always a single, correct adjustment.
     */
    public function updated(FeedRecord $feedRecord): void
    {
        // Only rebuild if a field that affects the consumption row changed.
        $relevant = $feedRecord->wasChanged([
            'feed_used',
            'inventory_item_id',
            'poultry_batch_id',
            'date',
        ]);

        if (! $relevant) {
            return;
        }

        // Delete the old consumption row (observer restores stock).
        InventoryConsumption::where('source_type', 'feed')
            ->where('source_id', $feedRecord->id)
            ->delete();

        // Re-create from the current values.
        if ($feedRecord->inventory_item_id && $feedRecord->inventoryItem) {
            $item = $feedRecord->inventoryItem;

            InventoryConsumption::create([
                'inventory_item_id' => $feedRecord->inventory_item_id,
                'poultry_batch_id'  => $feedRecord->poultry_batch_id,
                'quantity_used'     => $feedRecord->feed_used,
                'date'              => $feedRecord->date,
                'recorded_by_id'    => $feedRecord->recorded_by_id,
                'source_type'       => 'feed',
                'source_id'         => $feedRecord->id,
                'unit_cost_at_time' => $item->cost_per_unit,
                'total_cost'        => $feedRecord->feed_used * $item->cost_per_unit,
            ]);
        }
    }

    /**
     * On delete, remove the linked consumption row.
     *
     * Do NOT touch inventory stock directly here. The
     * InventoryConsumptionObserver::deleted handler restores it once.
     */
    public function deleted(FeedRecord $feedRecord): void
    {
        if (! $feedRecord->inventory_item_id) {
            return;
        }

        InventoryConsumption::where('source_type', 'feed')
            ->where('source_id', $feedRecord->id)
            ->delete();
    }
}