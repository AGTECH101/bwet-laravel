<?php

namespace App\Models\Poultry;

use App\Models\Sector;
use App\Models\User;
use App\Services\Poultry\BatchCalculationService;
use App\Services\Poultry\BatchTriggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Batch extends Model
{
    use HasFactory;

    protected $table = 'poultry_batches';

    protected $fillable = [
        'batch_id', 'name', 'hatchery', 'start_date', 'starting_flock',
        'remaining_flock', 'phase', 'pen_id', 'selling_price_per_kg',
        'selling_price_per_carton', 'initial_chicken_cost', 'status',
        'closed_at', 'current_age_days', 'total_mortality', 'total_culls',
        'total_slaughter', 'total_feed_used', 'bags_consumed',
        'total_weight_gain', 'current_ifcr', 'current_cfcr',
        'current_marginal_profit_percent', 'total_expenses',
        'cost_allocated_so_far', 'peak_profit', 'profit_margin_used',
        'stop_loss_used_percent', 'is_manual_mode', 'manual_mode_reason',
        'manual_mode_enabled_by_id', 'manual_mode_enabled_at',
        'created_by_id', 'sector_id'
    ];

    protected $casts = [
        'start_date' => 'date',
        'closed_at' => 'datetime',
        'manual_mode_enabled_at' => 'datetime',
        'is_manual_mode' => 'boolean',
        'selling_price_per_kg' => 'decimal:2',
        'selling_price_per_carton' => 'decimal:2',
        'initial_chicken_cost' => 'decimal:2',
        'total_feed_used' => 'decimal:3',
        'bags_consumed' => 'decimal:2',
        'total_weight_gain' => 'decimal:3',
        'current_ifcr' => 'decimal:4',
        'current_cfcr' => 'decimal:4',
        'current_marginal_profit_percent' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'cost_allocated_so_far' => 'decimal:2',
        'peak_profit' => 'decimal:2',
        'profit_margin_used' => 'decimal:2',
        'stop_loss_used_percent' => 'decimal:2',
    ];

    // Relationships
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function pen()
    {
        return $this->belongsTo(Pen::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function manualModeEnabledBy()
    {
        return $this->belongsTo(User::class, 'manual_mode_enabled_by_id');
    }

    public function flockRecords()
    {
        return $this->hasMany(FlockRecord::class, 'poultry_batch_id');
    }

    public function weightRecords()
    {
        return $this->hasMany(WeightRecord::class, 'poultry_batch_id');
    }

    public function feedRecords()
    {
        return $this->hasMany(FeedRecord::class, 'poultry_batch_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'poultry_batch_id');
    }

    public function inventoryConsumptions()
    {
        return $this->hasMany(InventoryConsumption::class, 'poultry_batch_id');
    }

    public function weighingSchedules()
    {
        return $this->hasMany(WeighingSchedule::class, 'poultry_batch_id');
    }

    public function performanceMetrics()
    {
        return $this->hasMany(PerformanceMetric::class, 'poultry_batch_id');
    }

    public function investorInvestments()
    {
        return $this->hasMany(InvestorInvestment::class, 'poultry_batch_id');
    }

    // Business logic delegates to services
    public function calculateRequiredSampleSize(): int
    {
        return BatchCalculationService::calculateRequiredSampleSize($this->remaining_flock);
    }

    public function getCurrentAverageWeight(): float
    {
        return BatchCalculationService::getCurrentAverageWeight($this);
    }

    public function getDressedWeightPerBird(): float
    {
        return BatchCalculationService::getDressedWeightPerBird($this);
    }

    public function getCostPerBird(): float
    {
        return BatchCalculationService::getCostPerBird($this);
    }

    public function getCostPerKg(): float
    {
        return BatchCalculationService::getCostPerKg($this);
    }

    public function getSellingPricePerBird(): float
    {
        return BatchCalculationService::getSellingPricePerBird($this);
    }

    public function getCalculatedSellingPricePerKg(): float
    {
        return BatchCalculationService::getCalculatedSellingPricePerKg($this);
    }

    public function getFinancialMetrics(): array
    {
        return BatchCalculationService::getFinancialMetrics($this);
    }

    public function checkSlaughterTriggers(): array
    {
        return BatchTriggerService::checkSlaughterTriggers($this);
    }

    public function allocateCostForSlaughter(int $numberSlaughtered, ?int $oldRemaining = null, ?float $oldTotalInvestment = null): float
    {
        return BatchCalculationService::allocateCostForSlaughter($this, $numberSlaughtered, $oldRemaining, $oldTotalInvestment);
    }

    public function updateCachedMetrics()
    {
        BatchCalculationService::updateCachedMetrics($this);
    }
}