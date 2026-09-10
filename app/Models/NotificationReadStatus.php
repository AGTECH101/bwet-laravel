<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationReadStatus extends Model
{
    protected $table = 'notification_read_statuses';

    protected $fillable = ['user_id', 'notification_id', 'is_read', 'read_at'];

    protected $casts = ['is_read' => 'boolean', 'read_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
}