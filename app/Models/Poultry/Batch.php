<?php

namespace App\Models\Poultry;

use App\Models\Sector;
use App\Models\User;
use App\Models\BatchStateMigration;
use App\Services\Poultry\BatchCalculationService;
use App\Services\Poultry\BatchTriggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Batch extends Model
{
    use HasFactory;

    protected $table = 'poultry_batches';

    protected $fillable = [
        'batch_id',
        'name',
        'hatchery',
        'start_date',
        'starting_flock',
        'remaining_flock',
        'phase',
        'pen_id',
        'selling_price_per_kg',
        'selling_price_per_carton',
        'initial_chicken_cost',
        'status',
        'closed_at',
        'current_age_days',
        'total_mortality',
        'total_culls',
        'total_slaughter',
        'total_feed_used',
        'bags_consumed',
        'total_weight_gain',
        'current_ifcr',
        'current_cfcr',
        'current_marginal_profit_percent',
        'total_expenses',
        'cost_allocated_so_far',
        'peak_profit',
        'profit_margin_used',
        'stop_loss_used_percent',
        'is_manual_mode',
        'manual_mode_reason',
        'manual_mode_enabled_by_id',
        'manual_mode_enabled_at',
        'created_by_id',
        'sector_id',
        // Checkpoint fields
        'current_average_weight',
        'current_count',
        'current_weight_kg',
        'current_cost',
        'current_average_cost',
        // Mortality fields
        'historical_mortality',
        'pen_mortality',
        'mortality_rate',
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
        // Checkpoint casts
        'current_average_weight' => 'decimal:3',
        'current_count' => 'integer',
        'current_weight_kg' => 'decimal:3',
        'current_cost' => 'decimal:2',
        'current_average_cost' => 'decimal:2',
        // Mortality casts
        'historical_mortality' => 'decimal:3',
        'pen_mortality' => 'decimal:3',
        'mortality_rate' => 'decimal:2',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

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

    public function stateMigrations()
    {
        return $this->hasMany(BatchStateMigration::class, 'source_batch_id');
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    /**
     * Dynamic age in days (always live).
     */
    public function getAgeDaysAttribute(): int
    {
        if (!$this->start_date) {
            return 0;
        }
        return $this->start_date->diffInDays(now());
    }

    /**
     * Dynamic mortality rate.
     */
    public function getMortalityRateAttribute(): float
    {
        return $this->starting_flock > 0
            ? ($this->total_mortality / $this->starting_flock) * 100
            : 0;
    }

    /**
     * Dynamic pen mortality rate.
     */
    public function getPenMortalityRateAttribute(): float
    {
        return $this->starting_flock > 0
            ? ($this->pen_mortality / $this->starting_flock) * 100
            : 0;
    }

    // ============================================================
    // STATE MANAGEMENT METHODS
    // ============================================================

    /**
     * Get the current state of the batch as an array.
     * Used in audit logging and transfers.
     */
    public function getCurrentState(): array
    {
        return [
            'count' => (int) $this->current_count,
            'weight' => (float) $this->current_weight_kg,
            'cost' => (float) $this->current_cost,
            'avg_weight' => (float) $this->current_average_weight,
            'avg_cost' => (float) $this->current_average_cost,
        ];
    }

    /**
     * Update the batch state and log the migration.
     *
     * @param array $changes  Keys: 'count', 'weight', 'cost', 'mortality', 'feed', 'weight_gain'
     * @param string $type    migration_type: feed, expense, mortality, cull, slaughter, transfer_out, transfer_in, weight_gain
     * @param Batch|null $destination  For transfers
     * @param User|null $user  User performing the action
     */
    public function updateState(array $changes, string $type, ?Batch $destination = null, ?User $user = null): void
    {
        DB::transaction(function () use ($changes, $type, $destination, $user) {
            $sourceBefore = $this->getCurrentState();

            if (isset($changes['count'])) {
                $this->current_count += $changes['count'];
            }
            if (isset($changes['weight'])) {
                $this->current_weight_kg += $changes['weight'];
            }
            if (isset($changes['cost'])) {
                $this->current_cost += $changes['cost'];
            }
            if (isset($changes['mortality'])) {
                $this->total_mortality += $changes['mortality'];
                $this->historical_mortality += $changes['mortality'];
            }
            if (isset($changes['feed'])) {
                $this->total_feed_used += $changes['feed'];
            }
            if (isset($changes['weight_gain'])) {
                $this->total_weight_gain += $changes['weight_gain'];
            }

            // Ensure no negative values
            $this->current_count = max(0, $this->current_count);
            $this->current_weight_kg = max(0, $this->current_weight_kg);
            $this->current_cost = max(0, $this->current_cost);
            $this->total_mortality = max(0, $this->total_mortality);
            $this->historical_mortality = max(0, $this->historical_mortality);
            $this->total_feed_used = max(0, $this->total_feed_used);
            $this->total_weight_gain = max(0, $this->total_weight_gain);

            $this->current_average_weight = $this->current_count > 0
                ? $this->current_weight_kg / $this->current_count
                : 0;
            $this->current_average_cost = $this->current_count > 0
                ? $this->current_cost / $this->current_count
                : 0;
            $this->mortality_rate = $this->starting_flock > 0
                ? ($this->total_mortality / $this->starting_flock) * 100
                : 0;

            $this->remaining_flock = $this->current_count;
            $this->save();

            // Log migration
            BatchStateMigration::create([
                'source_batch_id' => $this->id,
                'destination_batch_id' => $destination?->id,
                'migration_type' => $type,
                'count_moved' => $changes['count'] ?? 0,
                'weight_moved' => $changes['weight'] ?? 0,
                'cost_moved' => $changes['cost'] ?? 0,
                'mortality_moved' => $changes['mortality'] ?? 0,
                'feed_moved' => $changes['feed'] ?? 0,
                'weight_gain_moved' => $changes['weight_gain'] ?? 0,
                'source_state_before' => $sourceBefore,
                'destination_state_before' => $destination ? $destination->getCurrentState() : null,
                'created_by_id' => $user?->id ?? auth()->id(),
            ]);

            // If transfer, apply inverse to destination
            if ($destination && $destination->id !== $this->id) {
                $destChanges = [
                    'count' => -($changes['count'] ?? 0),
                    'weight' => -($changes['weight'] ?? 0),
                    'cost' => -($changes['cost'] ?? 0),
                    'mortality' => -($changes['mortality'] ?? 0),
                    'feed' => -($changes['feed'] ?? 0),
                    'weight_gain' => -($changes['weight_gain'] ?? 0),
                ];
                $destination->applyStateChangeDirectly($destChanges, 'transfer_in', $this, $user);
            }
        });
    }

    /**
     * Direct state update (used for destination of transfers to avoid recursion).
     */
    public function applyStateChangeDirectly(array $changes, string $type, ?Batch $source = null, ?User $user = null): void
    {
        DB::transaction(function () use ($changes, $type, $source, $user) {
            $before = $this->getCurrentState();

            if (isset($changes['count'])) {
                $this->current_count += $changes['count'];
            }
            if (isset($changes['weight'])) {
                $this->current_weight_kg += $changes['weight'];
            }
            if (isset($changes['cost'])) {
                $this->current_cost += $changes['cost'];
            }
            if (isset($changes['mortality'])) {
                $this->total_mortality += $changes['mortality'];
                $this->historical_mortality += $changes['mortality'];
            }
            if (isset($changes['feed'])) {
                $this->total_feed_used += $changes['feed'];
            }
            if (isset($changes['weight_gain'])) {
                $this->total_weight_gain += $changes['weight_gain'];
            }

            $this->current_count = max(0, $this->current_count);
            $this->current_weight_kg = max(0, $this->current_weight_kg);
            $this->current_cost = max(0, $this->current_cost);
            $this->total_mortality = max(0, $this->total_mortality);
            $this->historical_mortality = max(0, $this->historical_mortality);
            $this->total_feed_used = max(0, $this->total_feed_used);
            $this->total_weight_gain = max(0, $this->total_weight_gain);

            $this->current_average_weight = $this->current_count > 0
                ? $this->current_weight_kg / $this->current_count
                : 0;
            $this->current_average_cost = $this->current_count > 0
                ? $this->current_cost / $this->current_count
                : 0;
            $this->mortality_rate = $this->starting_flock > 0
                ? ($this->total_mortality / $this->starting_flock) * 100
                : 0;

            $this->remaining_flock = $this->current_count;
            $this->save();

            BatchStateMigration::create([
                'source_batch_id' => $source?->id ?? $this->id,
                'destination_batch_id' => $this->id,
                'migration_type' => $type,
                'count_moved' => $changes['count'] ?? 0,
                'weight_moved' => $changes['weight'] ?? 0,
                'cost_moved' => $changes['cost'] ?? 0,
                'mortality_moved' => $changes['mortality'] ?? 0,
                'feed_moved' => $changes['feed'] ?? 0,
                'weight_gain_moved' => $changes['weight_gain'] ?? 0,
                'source_state_before' => $source ? $source->getCurrentState() : null,
                'destination_state_before' => $before,
                'created_by_id' => $user?->id ?? auth()->id(),
            ]);
        });
    }

    /**
     * Add weight gain from a weight record.
     */
    public function addWeightGain(float $weightGain): void
    {
        DB::transaction(function () use ($weightGain) {
            $this->total_weight_gain += $weightGain;
            $this->current_weight_kg += $weightGain;
            $this->current_average_weight = $this->current_count > 0
                ? $this->current_weight_kg / $this->current_count
                : 0;
            $this->save();

            BatchStateMigration::create([
                'source_batch_id' => $this->id,
                'destination_batch_id' => null,
                'migration_type' => 'weight_gain',
                'count_moved' => 0,
                'weight_moved' => $weightGain,
                'cost_moved' => 0,
                'mortality_moved' => 0,
                'feed_moved' => 0,
                'weight_gain_moved' => $weightGain,
                'source_state_before' => null,
                'destination_state_before' => null,
                'created_by_id' => auth()->id(),
            ]);
        });
    }

    // ============================================================
    // BUSINESS LOGIC DELEGATES
    // ============================================================

    public function calculateRequiredSampleSize(): int
    {
        return BatchCalculationService::calculateRequiredSampleSize($this->current_count ?? 0);
    }

    public function calculateTotalInvestment(): float
    {
        return BatchCalculationService::calculateTotalInvestment($this);
    }

    public function getCurrentAverageWeight(): float
    {
        return (float) $this->current_average_weight;
    }

    public function getDressedWeightPerBird(): float
    {
        return BatchCalculationService::getDressedWeightPerBird($this);
    }

    public function getCostPerBird(): float
    {
        return (float) $this->current_average_cost;
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