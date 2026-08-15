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

    public static function getValue(string $key, $default = null)
    {
        return static::getValueForDate($key, now(), $default);
    }

    public function update(array $attributes = [], array $options = [])
    {
        if (empty($attributes)) {
            return parent::update($attributes, $options);
        }

        $newVersion = $this->replicate();
        $newVersion->fill($attributes);
        $newVersion->effective_from = $attributes['effective_from'] ?? now();
        $newVersion->updated_by_id = $attributes['updated_by_id'] ?? auth()->id();
        $newVersion->save();

        $this->forceFill($newVersion->getAttributes());
        $this->syncOriginal();

        return $newVersion;
    }

    public static function getValueForDate(string $key, $date, $default = null)
    {
        $var = static::where('key', $key)
            ->where('is_active', true)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $date);
            })
            ->where(function ($query) use ($date) {
                $query->where(function ($inner) use ($date) {
                    $inner->whereNotNull('effective_from')
                        ->where('effective_from', '<=', $date);
                })->orWhere(function ($inner) use ($date) {
                    $inner->whereNull('effective_from')
                        ->where(function ($innerDate) use ($date) {
                            $innerDate->where('created_at', '<=', $date)
                                ->orWhere('updated_at', '<=', $date);
                        });
                });
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('updated_at')
            ->first();

        if (!$var) {
            return $default;
        }

        return match ($var->data_type) {
            'integer' => (int) $var->value,
            'decimal', 'percentage' => (float) $var->value,
            'boolean' => filter_var($var->value, FILTER_VALIDATE_BOOLEAN),
            default => $var->value,
        };
    }

    public static function createVersion(array $attributes): self
    {
        $attributes['effective_from'] = $attributes['effective_from'] ?? now();

        return static::create($attributes);
    }
}