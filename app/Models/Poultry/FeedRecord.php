<?php

namespace App\Models\Poultry;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FeedRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'poultry_batch_id', 'inventory_item_id', 'date', 'feed_used',
        'feed_cost_per_kg', 'total_feed_cost', 'feed_per_bird',
        'recorded_by_id'
    ];

    protected $casts = [
        'date' => 'date',
        'feed_used' => 'decimal:3',
        'feed_cost_per_kg' => 'decimal:2',
        'total_feed_cost' => 'decimal:2',
        'feed_per_bird' => 'decimal:3',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'poultry_batch_id');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }
}