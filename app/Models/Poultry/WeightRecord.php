<?php

namespace App\Models\Poultry;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WeightRecord extends Model
{
    use HasFactory;

    protected $table = 'weight_records';

    protected $fillable = [
        'poultry_batch_id', 'date', 'individual_weights', 'birds_weighed',
        'total_weight', 'average_weight', 'coefficient_variation',
        'cv_status', 'is_valid_sample', 'expected_weight', 'notes',
        'recorded_by_id',
    ];

    protected $casts = [
        'date' => 'date',
        'individual_weights' => 'array',
        'total_weight' => 'decimal:3',
        'average_weight' => 'decimal:3',
        'coefficient_variation' => 'decimal:2',
        'expected_weight' => 'decimal:3',
        'is_valid_sample' => 'boolean',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'poultry_batch_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    /**
     * Compute metrics from individual_weights.
     *
     * CV policy:
     *   < 10  → 'excellent'  (badge: green)
     *   < 12  → 'caution'    (badge: blue)
     *   < 15  → 'warning'    (badge: yellow)
     *   >= 15 → 'high'       (badge: orange, warning flash on store/update)
     *
     * Every record is flagged is_valid_sample = true so downstream
     * metrics (average weight, FCR, batch calculations) use it
     * regardless of variation.
     */
    public function calculateMetrics(): void
    {
        $weights = is_array($this->individual_weights) ? $this->individual_weights : [];
        if (empty($weights)) {
            return;
        }

        $num = count($weights);
        $this->birds_weighed = $num;
        $this->total_weight = array_sum($weights);
        $this->average_weight = $this->total_weight / $num;

        $mean = (float) $this->average_weight;
        $variance = array_sum(array_map(fn ($w) => ($w - $mean) ** 2, $weights)) / $num;
        $stddev = sqrt($variance);
        $cv = $mean > 0 ? ($stddev / $mean) * 100 : 0;
        $this->coefficient_variation = round($cv, 2);

        if ($cv >= 15) {
            $this->cv_status = 'high';
        } elseif ($cv >= 12) {
            $this->cv_status = 'warning';
        } elseif ($cv >= 10) {
            $this->cv_status = 'caution';
        } else {
            $this->cv_status = 'excellent';
        }

        $this->is_valid_sample = true;
        $this->expected_weight = $this->calculateExpectedWeight();
    }

    protected function calculateExpectedWeight(): float
    {
        $age = $this->batch?->age_days ?? 0;

        if ($age <= 0) {
            return 0.045;
        }

        if ($age <= 7) return 0.18;
        if ($age <= 14) return 0.45;
        if ($age <= 21) return 0.85;
        if ($age <= 28) return 1.30;
        if ($age <= 35) return 1.80;
        if ($age <= 42) return 2.20;
        if ($age <= 49) return 2.50;

        return 2.70;
    }
}