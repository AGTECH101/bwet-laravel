<?php

namespace App\Models\Poultry;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvestorInvestment extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_id', 'poultry_batch_id', 'amount_invested',
        'investment_date', 'batch_total_cost_at_investment',
        'investment_percentage', 'is_active'
    ];

    protected $casts = [
        'investment_date' => 'date',
        'amount_invested' => 'decimal:2',
        'batch_total_cost_at_investment' => 'decimal:2',
        'investment_percentage' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function investor()
    {
        return $this->belongsTo(User::class, 'investor_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'poultry_batch_id');
    }
}