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
        'notes', 'recorded_by_id'
    ];

    protected $casts = [
        'date' => 'date',
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