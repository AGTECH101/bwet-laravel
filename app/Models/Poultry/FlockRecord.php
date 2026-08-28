<?php

namespace App\Models\Poultry;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FlockRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'poultry_batch_id', 'date', 'mortality', 'culls', 'slaughter',
        'notes', 'recorded_by_id', 'allocated_cost'  // <-- ADD THIS
    ];

    protected $casts = [
        'date' => 'date',
        'allocated_cost' => 'decimal:2',  // <-- ADD THIS
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