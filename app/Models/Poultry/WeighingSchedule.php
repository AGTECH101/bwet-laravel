<?php

namespace App\Models\Poultry;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WeighingSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'poultry_batch_id', 'scheduled_date', 'is_completed',
        'completed_at', 'admin_notified_missed'
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_at' => 'datetime',
        'is_completed' => 'boolean',
        'admin_notified_missed' => 'boolean',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'poultry_batch_id');
    }

    public static function generateForBatch(Batch $batch, ?int $frequency = null)
    {
        $frequency = $frequency ?? (int) \App\Models\SystemVariable::getValue('weighing_frequency_days', 4);
        $schedules = [];
        $currentDate = $batch->start_date->copy();

        if ($batch->phase === 'brooding') {
            for ($week = 1; $week <= 2; $week++) {
                $date = $batch->start_date->copy()->addDays($week * 7);
                $schedules[] = new static(['scheduled_date' => $date]);
            }
        } else {
            for ($i = 1; $i <= 20; $i++) {
                $date = $batch->start_date->copy()->addDays($frequency * $i);
                $schedules[] = new static(['scheduled_date' => $date]);
            }
        }

        return $batch->weighingSchedules()->saveMany($schedules);
    }
}