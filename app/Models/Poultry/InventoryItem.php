<?php

namespace App\Models\Poultry;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'category', 'unit', 'quantity_in_stock', 'quantity_used',
        'minimum_quantity', 'vendor', 'cost_per_unit', 'is_active',
        'status', 'killed_reason', 'killed_by_id', 'killed_at',
        'created_by_id'
    ];

    protected $casts = [
        'quantity_in_stock' => 'decimal:3',
        'quantity_used' => 'decimal:3',
        'minimum_quantity' => 'decimal:3',
        'cost_per_unit' => 'decimal:2',
        'is_active' => 'boolean',
        'killed_at' => 'datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function killedBy()
    {
        return $this->belongsTo(User::class, 'killed_by_id');
    }

    public function consumptions()
    {
        return $this->hasMany(InventoryConsumption::class);
    }

    // Helpers
    public function isLowStock(): bool
    {
        return $this->quantity_in_stock <= $this->minimum_quantity;
    }

    public function isOutOfStock(): bool
    {
        return $this->quantity_in_stock <= 0;
    }

    public function getTotalValue(): float
    {
        return $this->quantity_in_stock * $this->cost_per_unit;
    }

    public function kill(User $user, ?string $reason = null)
    {
        $this->status = 'killed';
        $this->killed_by_id = $user->id;
        $this->killed_at = now();
        $this->killed_reason = $reason;
        $this->save();
    }
}