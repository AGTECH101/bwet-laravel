<?php

namespace App\Models\Poultry;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pen extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'pen_code', 'pen_type', 'capacity', 'is_active',
        'notes', 'current_batch_id', 'created_by_id'
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function currentBatch()
    {
        return $this->belongsTo(Batch::class, 'current_batch_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('current_batch_id')
                  ->orWhereHas('currentBatch', function ($sq) {
                      $sq->whereIn('status', ['closed', 'completed']);
                  });
            });
    }
}