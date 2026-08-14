<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObservationCategory extends Model
{
    protected $fillable = ['name', 'description', 'is_active', 'created_by_id'];

    protected $casts = ['is_active' => 'boolean'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function reports()
    {
        return $this->hasMany(ObservationReport::class, 'category_id');
    }
}