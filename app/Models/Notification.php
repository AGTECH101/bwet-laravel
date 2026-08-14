<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'notification_type', 'title', 'message', 'batch_id',
        'observation_report_id', 'is_active', 'created_by_id'
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function batch()
    {
        return $this->belongsTo(Poultry\Batch::class);
    }

    public function observationReport()
    {
        return $this->belongsTo(ObservationReport::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function readStatuses()
    {
        return $this->hasMany(NotificationReadStatus::class);
    }

    public static function createGlobal(string $type, string $title, string $message, ?User $createdBy = null, ?Poultry\Batch $batch = null, ?ObservationReport $report = null)
    {
        $notification = static::create([
            'notification_type' => $type,
            'title' => $title,
            'message' => $message,
            'created_by_id' => $createdBy?->id,
            'batch_id' => $batch?->id,
            'observation_report_id' => $report?->id,
        ]);

        // Create read status for all approved users
        $users = User::where('is_approved', true)->get();
        foreach ($users as $user) {
            NotificationReadStatus::create([
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'is_read' => false,
            ]);
        }

        return $notification;
    }
}