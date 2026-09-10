<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'cost', 'estimated_revenue',
        'date_of_resumption', 'status', 'is_live'
    ];

    protected $casts = [
        'is_live' => 'boolean',
        'date_of_resumption' => 'date',
    ];
}