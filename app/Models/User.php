<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, TwoFactorAuthenticatable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'farm_location',
        'is_approved', 'approved_by_id', 'approved_at'
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_recovery_codes', 'two_factor_secret'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if ($user->role === 'admin') {
                $user->is_approved = true;
            }
        });
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    // Relationships to poultry models (if needed)
    public function createdBatches()
    {
        return $this->hasMany(Poultry\Batch::class, 'created_by_id');
    }
}