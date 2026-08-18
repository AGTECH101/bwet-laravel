<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

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

    public function notificationReadStatuses()
    {
        return $this->hasMany(NotificationReadStatus::class);
    }

    // Relationships to poultry models (if needed)
    public function createdBatches()
    {
        return $this->hasMany(Poultry\Batch::class, 'created_by_id');
    }

    public function investorInvestments()
    {
        return $this->hasMany(Poultry\InvestorInvestment::class, 'investor_id');
    }
}