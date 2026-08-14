<?php

namespace App\Models\Poultry;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WeightRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'poultry_batch_id', 'date', 'individual_weights', 'birds_weighed',
        'total_weight', 'average_weight', 'coefficient_variation',
        'cv_status', 'is_valid_sample', 'expected_weight', 'notes',
        'recorded_by_id'
    ];

    protected $casts = [
        'date' => 'date',
        'individual_weights' => 'array',
        'total_weight' => 'decimal:3',
        'average_weight' => 'decimal:3',
        'coefficient_variation' => 'decimal:2',
        'expected_weight' => 'decimal:3',
        'is_valid_sample' => 'boolean',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'poultry_batch_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }
}