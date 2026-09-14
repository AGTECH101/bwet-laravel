<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemVariable extends Model
{
    protected $fillable = [
        'name', 'key', 'category', 'value', 'data_type',
        'description', 'is_active', 'updated_by_id', 'effective_from',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_from' => 'datetime',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * Get the current value for a system variable.
     * Returns the most recent active row.
     */
    public static function getValue(string $key, $default = null)
    {
        $var = static::where('key', $key)
            ->where('is_active', true)
            ->orderByDesc('effective_from')
            ->orderByDesc('updated_at')
            ->first();

        if (!$var) {
            return $default;
        }

        return static::castValue($var->value, $var->data_type);
    }

    /**
     * Get the value as of a specific date (for historical lookups).
     */
    public static function getValueForDate(string $key, $date, $default = null)
    {
        $var = static::where('key', $key)
            ->where('is_active', true)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $date);
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('updated_at')
            ->first();

        if (!$var) {
            return $default;
        }

        return static::castValue($var->value, $var->data_type);
    }

    /**
     * Cast a raw string value to its declared data type.
     */
    protected static function castValue($value, $dataType)
    {
        return match ($dataType) {
            'integer' => (int) $value,
            'decimal', 'percentage' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }

    /**
     * Override update to be a straight update.
     *
     * We deliberately removed the old "versioning" logic that created
     * duplicate rows — it caused the UI and the price calculator to
     * disagree on the current value. Now there is exactly one active
     * row per key.
     */
    public function update(array $attributes = [], array $options = [])
    {
        // Track who updated it and when
        if (!isset($attributes['updated_by_id']) && auth()->check()) {
            $attributes['updated_by_id'] = auth()->id();
        }

        return parent::update($attributes, $options);
    }

    /**
     * Helper for creating a variable the first time.
     */
    public static function createVersion(array $attributes): self
    {
        $attributes['effective_from'] = $attributes['effective_from'] ?? now();

        return static::create($attributes);
    }
}