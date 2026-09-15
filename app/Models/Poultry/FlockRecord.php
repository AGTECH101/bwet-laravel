<?php

namespace App\Models\Poultry;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FlockRecord extends Model
{
    use HasFactory;

    protected $table = 'flock_records';

    protected $fillable = [
        'poultry_batch_id',
        'date',
        'mortality',
        'culls',
        'slaughter',
        'slaughter_avg_weight',
        'notes',
        'recorded_by_id',
        'allocated_cost',
    ];

    protected $casts = [
        'date' => 'date',
        'slaughter_avg_weight' => 'decimal:3',
        'allocated_cost' => 'decimal:2',
    ];

    /**
     * Coerce null / empty strings to 0 for the three counter columns.
     *
     * The database columns are NOT NULL with DEFAULT 0, but that default
     * only fires when the column is omitted from an INSERT. Laravel's
     * ConvertEmptyStringsToNull middleware turns blank form fields into
     * null, which MySQL strict mode then rejects (error 1048).
     *
     * Handling it at the model boundary catches every write path:
     * forms, Control Panel edits, seeders, and API submissions.
     */
    protected function mortality(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (int) ($value ?? 0),
            set: fn ($value) => ($value === null || $value === '') ? 0 : (int) $value,
        );
    }

    protected function culls(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (int) ($value ?? 0),
            set: fn ($value) => ($value === null || $value === '') ? 0 : (int) $value,
        );
    }

    protected function slaughter(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (int) ($value ?? 0),
            set: fn ($value) => ($value === null || $value === '') ? 0 : (int) $value,
        );
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'poultry_batch_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    /**
     * Get the weight used for slaughter weight subtraction.
     * If slaughter_avg_weight is provided, use it; otherwise fall back to batch average.
     */
    public function getSlaughterWeightUsed(): float
    {
        if ($this->slaughter_avg_weight && $this->slaughter_avg_weight > 0) {
            return (float) $this->slaughter_avg_weight;
        }

        return $this->batch ? (float) $this->batch->current_average_weight : 0;
    }
}