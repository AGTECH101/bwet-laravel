<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'price_per_bird',
        'price_per_kg',
        'price_per_carton',
        'effective_date',
        'notes',
        'is_active',
        'set_by_id',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'is_active' => 'boolean',
        'price_per_bird' => 'decimal:2',
        'price_per_kg' => 'decimal:2',
        'price_per_carton' => 'decimal:2',
    ];

    public function setBy()
    {
        return $this->belongsTo(User::class, 'set_by_id');
    }

    public static function getCurrentPrices()
    {
        return static::where('is_active', true)->orderBy('effective_date', 'desc')->first();
    }

    public static function getPriceHistory($days = 30)
    {
        return static::where('effective_date', '>=', now()->subDays($days))
            ->orderBy('effective_date', 'desc')
            ->get();
    }
}