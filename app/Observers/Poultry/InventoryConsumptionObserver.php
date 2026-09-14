<?php

namespace App\Observers\Poultry;

use App\Models\Poultry\InventoryConsumption;
use App\Models\Poultry\InventoryItem;

class InventoryConsumptionObserver
{
    /**
     * Deduct stock when a consumption row is created.
     */
    public function created(InventoryConsumption $consumption): void
    {
        $item = $consumption->inventoryItem ?? InventoryItem::find($consumption->inventory_item_id);
        if (! $item) {
            return;
        }

        $qty = (float) $consumption->quantity_used;

        $item->quantity_in_stock = max(0, (float) $item->quantity_in_stock - $qty);
        $item->quantity_used     = (float) $item->quantity_used + $qty;
        $item->save();
    }

    /**
     * Adjust stock when a consumption row changes.
     *
     * If only the quantity changed → adjust the delta in place.
     * If the item changed             → restore the old item, deduct from the new one.
     */
    public function updated(InventoryConsumption $consumption): void
    {
        $oldQty = (float) $consumption->getOriginal('quantity_used');
        $newQty = (float) $consumption->quantity_used;

        $oldItemId = (int) $consumption->getOriginal('inventory_item_id');
        $newItemId = (int) $consumption->inventory_item_id;

        if ($oldItemId === $newItemId) {
            $item = InventoryItem::find($newItemId);
            if (! $item) {
                return;
            }

            $delta = $newQty - $oldQty;
            $item->quantity_in_stock = max(0, (float) $item->quantity_in_stock - $delta);
            $item->quantity_used     = (float) $item->quantity_used + $delta;
            $item->save();
            return;
        }

        // Item changed: restore on old, deduct on new.
        $oldItem = InventoryItem::find($oldItemId);
        if ($oldItem) {
            $oldItem->quantity_in_stock = (float) $oldItem->quantity_in_stock + $oldQty;
            $oldItem->quantity_used     = max(0, (float) $oldItem->quantity_used - $oldQty);
            $oldItem->save();
        }

        $newItem = InventoryItem::find($newItemId);
        if ($newItem) {
            $newItem->quantity_in_stock = max(0, (float) $newItem->quantity_in_stock - $newQty);
            $newItem->quantity_used     = (float) $newItem->quantity_used + $newQty;
            $newItem->save();
        }
    }

    /**
     * Restore stock when a consumption row is deleted.
     */
    public function deleted(InventoryConsumption $consumption): void
    {
        $item = InventoryItem::find($consumption->inventory_item_id);
        if (! $item) {
            return;
        }

        $qty = (float) $consumption->quantity_used;

        $item->quantity_in_stock = (float) $item->quantity_in_stock + $qty;
        $item->quantity_used     = max(0, (float) $item->quantity_used - $qty);
        $item->save();
    }
}