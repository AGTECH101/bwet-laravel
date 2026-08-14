<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoryQuery extends Model
{
    protected $fillable = [
        'name', 'query_type', 'date_from', 'date_to', 'user_filter_id',
        'batch_filter_id', 'category_filter', 'min_amount', 'max_amount',
        'result_count', 'last_executed', 'execution_time_ms',
        'created_by_id', 'is_shared'
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'last_executed' => 'datetime',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'is_shared' => 'boolean',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function userFilter()
    {
        return $this->belongsTo(User::class, 'user_filter_id');
    }

    public function batchFilter()
    {
        return $this->belongsTo(Poultry\Batch::class, 'batch_filter_id');
    }
}