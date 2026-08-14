<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemVariable extends Model
{
    protected $fillable = [
        'name', 'key', 'category', 'value', 'data_type',
        'description', 'is_active', 'updated_by_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public static function getValue(string $key, $default = null)
    {
        return Cache::remember("system_var_{$key}", 300, function () use ($key, $default) {
            $var = static::where('key', $key)->where('is_active', true)->first();
            if (!$var) {
                return $default;
            }
            return match ($var->data_type) {
                'integer' => (int) $var->value,
                'decimal', 'percentage' => (float) $var->value,
                'boolean' => filter_var($var->value, FILTER_VALIDATE_BOOLEAN),
                default => $var->value,
            };
        });
    }
}