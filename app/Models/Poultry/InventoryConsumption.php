<?php

namespace App\Models\Poultry;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id', 'poultry_batch_id', 'quantity_used', 'date',
        'unit_cost_at_time', 'total_cost', 'source_type', 'source_id',
        'recorded_by_id'
    ];

    protected $casts = [
        'date' => 'date',
        'quantity_used' => 'decimal:3',
        'unit_cost_at_time' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'poultry_batch_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }
}