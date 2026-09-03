<?php

namespace App\Models;

use App\Models\Poultry\Batch;
use Illuminate\Database\Eloquent\Model;

class BatchStateMigration extends Model
{
    protected $table = 'batch_state_migrations';

    protected $fillable = [
        'source_batch_id',
        'destination_batch_id',
        'migration_type',
        'count_moved',
        'weight_moved',
        'cost_moved',
        'mortality_moved',
        'feed_moved',
        'weight_gain_moved',
        'source_state_before',
        'destination_state_before',
        'created_by_id',
    ];

    protected $casts = [
        'source_state_before' => 'array',
        'destination_state_before' => 'array',
        'count_moved' => 'integer',
        'weight_moved' => 'decimal:3',
        'cost_moved' => 'decimal:2',
        'mortality_moved' => 'decimal:3',
        'feed_moved' => 'decimal:3',
        'weight_gain_moved' => 'decimal:3',
    ];

    public function sourceBatch()
    {
        return $this->belongsTo(Batch::class, 'source_batch_id');
    }

    public function destinationBatch()
    {
        return $this->belongsTo(Batch::class, 'destination_batch_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}