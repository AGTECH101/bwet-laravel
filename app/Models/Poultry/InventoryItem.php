<?php

namespace App\Models\Poultry;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryItem extends Model
{
    use HasFactory;

    /**
     * Conversion factor: 1 bag = 25 kg.
     * Applied on save; the stored record is always in kg.
     */
    const KG_PER_BAG = 25;

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

    protected static function boot()
    {
        parent::boot();

        // Normalize bag-based submissions to kg on every save.
        //
        // If a user enters "10 bags at ₦5000/bag", we store it as
        // "250 kg at ₦200/kg" so every downstream calculation (feed
        // cost, inventory consumption, batch investment) uses kg
        // consistently. The 'bag' unit is therefore a display-only
        // shorthand at the input edge, never a stored state.
        static::saving(function (InventoryItem $item) {
            if ($item->unit === 'bag') {
                $item->quantity_in_stock  = (float) $item->quantity_in_stock  * self::KG_PER_BAG;
                $item->quantity_used      = (float) $item->quantity_used      * self::KG_PER_BAG;
                $item->minimum_quantity   = (float) $item->minimum_quantity   * self::KG_PER_BAG;
                $item->cost_per_unit      = (float) $item->cost_per_unit      / self::KG_PER_BAG;
                $item->unit               = 'kg';
            }
        });
    }

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
        return (float) $this->quantity_in_stock * (float) $this->cost_per_unit;
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