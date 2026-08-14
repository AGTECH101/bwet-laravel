<?php

namespace App\Models\Poultry;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerformanceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'poultry_batch_id', 'date', 'age_days', 'average_weight',
        'daily_feed', 'cumulative_feed', 'ifcr', 'cfcr',
        'marginal_profit_percent', 'adg'
    ];

    protected $casts = [
        'date' => 'date',
        'average_weight' => 'decimal:3',
        'daily_feed' => 'decimal:3',
        'cumulative_feed' => 'decimal:3',
        'ifcr' => 'decimal:4',
        'cfcr' => 'decimal:4',
        'marginal_profit_percent' => 'decimal:2',
        'adg' => 'decimal:4',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'poultry_batch_id');
    }

    public static function updateOrCreateForBatch(Batch $batch, ?string $date = null)
    {
        $date = $date ?? now()->toDateString();
        $ageDays = $batch->start_date->diffInDays($date);

        $latestWeight = $batch->weightRecords()->where('date', '<=', $date)->latest('date')->first();
        $averageWeight = $latestWeight ? $latestWeight->average_weight : 0;

        $dailyFeed = $batch->feedRecords()->where('date', $date)->sum('feed_used') ?? 0;
        $cumulativeFeed = $batch->feedRecords()->where('date', '<=', $date)->sum('feed_used') ?? 0;

        $ifcr = $batch->current_ifcr;
        $cfcr = $batch->current_cfcr;
        $profit = $batch->current_marginal_profit_percent;

        // ADG calculation
        $adg = null;
        if ($latestWeight) {
            $prevWeight = $batch->weightRecords()->where('date', '<', $date)->latest('date')->first();
            if ($prevWeight && $prevWeight->date != $latestWeight->date) {
                $daysDiff = $latestWeight->date->diffInDays($prevWeight->date);
                if ($daysDiff > 0) {
                    $adg = ($latestWeight->average_weight - $prevWeight->average_weight) / $daysDiff;
                }
            }
        }

        return static::updateOrCreate(
            ['poultry_batch_id' => $batch->id, 'date' => $date],
            [
                'age_days' => $ageDays,
                'average_weight' => $averageWeight,
                'daily_feed' => $dailyFeed,
                'cumulative_feed' => $cumulativeFeed,
                'ifcr' => $ifcr,
                'cfcr' => $cfcr,
                'marginal_profit_percent' => $profit,
                'adg' => $adg,
            ]
        );
    }
}