<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObservationReport extends Model
{
    protected $fillable = [
        'title', 'category_id', 'other_category', 'description',
        'affected_batch_ids', 'evidence_photos', 'status', 'priority',
        'reported_by_id', 'reported_at', 'reviewed_by_id', 'reviewed_at',
        'resolved_by_id', 'resolved_at', 'admin_response', 'actions_taken',
        'is_archived', 'requires_follow_up', 'follow_up_date'
    ];

    protected $casts = [
        'affected_batch_ids' => 'array',
        'evidence_photos' => 'array',
        'reported_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'follow_up_date' => 'date',
        'is_archived' => 'boolean',
        'requires_follow_up' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ObservationCategory::class, 'category_id');
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }
}