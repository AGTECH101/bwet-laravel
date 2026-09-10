<?php

namespace App\Models\Poultry;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FlockRecord extends Model
{
    use HasFactory;

    protected $table = 'flock_records';

    protected $fillable = [
        'poultry_batch_id',
        'date',
        'mortality',
        'culls',
        'slaughter',
        'slaughter_avg_weight',
        'notes',
        'recorded_by_id',
        'allocated_cost'
    ];

    protected $casts = [
        'date' => 'date',
        'slaughter_avg_weight' => 'decimal:3',
        'allocated_cost' => 'decimal:2',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'poultry_batch_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    /**
     * Get the weight used for slaughter weight subtraction.
     * If slaughter_avg_weight is provided, use it; otherwise fall back to batch average.
     */
    public function getSlaughterWeightUsed(): float
    {
        if ($this->slaughter_avg_weight && $this->slaughter_avg_weight > 0) {
            return (float) $this->slaughter_avg_weight;
        }

        return $this->batch ? (float) $this->batch->current_average_weight : 0;
    }
}