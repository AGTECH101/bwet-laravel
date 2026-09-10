<?php

namespace App\Observers\Poultry;

use App\Models\Poultry\InventoryConsumption;
use Illuminate\Support\Facades\DB;

class InventoryConsumptionObserver
{
    public function created(InventoryConsumption $consumption)
    {
        // Update inventory stock atomically
        DB::table('inventory_items')
            ->where('id', $consumption->inventory_item_id)
            ->update([
                'quantity_in_stock' => DB::raw("quantity_in_stock - {$consumption->quantity_used}"),
                'quantity_used' => DB::raw("quantity_used + {$consumption->quantity_used}"),
                'updated_at' => now(),
            ]);

        // Update batch metrics if batch is linked
        if ($consumption->poultry_batch_id) {
            $consumption->batch->updateCachedMetrics();
        }
    }

    public function deleted(InventoryConsumption $consumption)
    {
        // Restore stock
        DB::table('inventory_items')
            ->where('id', $consumption->inventory_item_id)
            ->update([
                'quantity_in_stock' => DB::raw("quantity_in_stock + {$consumption->quantity_used}"),
                'quantity_used' => DB::raw("quantity_used - {$consumption->quantity_used}"),
                'updated_at' => now(),
            ]);

        if ($consumption->poultry_batch_id) {
            $consumption->batch->updateCachedMetrics();
        }
    }
}