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
        'created_by_id', 'sector_id', 'current_average_weight',
        // NEW CHECKPOINT COLUMNS (added via migration)
        'current_count',
        'current_weight_kg',
        'current_cost',
        'current_average_weight',
        'current_average_cost',
        'total_weight_gain',
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
        'current_average_weight' => 'decimal:3',
        // New checkpoint fields
        'current_count' => 'integer',
        'current_weight_kg' => 'decimal:3',
        'current_cost' => 'decimal:2',
        'current_average_weight' => 'decimal:3',
        'current_average_cost' => 'decimal:2',
        'total_weight_gain' => 'decimal:3',
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

    public function stateMigrations()
    {
        return $this->hasMany(BatchStateMigration::class, 'source_batch_id');
    }

    // ---- DYNAMIC ACCESSOR FOR AGE (ALWAYS LIVE) ----
    public function getAgeDaysAttribute(): int
    {
        if (!$this->start_date) {
            return 0;
        }
        return $this->start_date->diffInDays(now());
    }

    // ---- STATE MANAGEMENT METHODS ----

    /**
     * Get the current state as an array.
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
     * @param array $changes  Keys: 'count', 'weight', 'cost' (increments to apply)
     * @param string $type   One of: feed, expense, mortality, cull, slaughter, transfer_out, transfer_in, weight_gain
     * @param Batch|null $destination  If this is a transfer, the destination batch
     * @param User|null $user         User performing the action (defaults to current auth user)
     */
    public function updateState(array $changes, string $type, ?Batch $destination = null, ?User $user = null): void
    {
        DB::transaction(function () use ($changes, $type, $destination, $user) {
            $sourceBefore = $this->getCurrentState();

            // Apply changes to the current batch
            if (isset($changes['count'])) {
                $this->current_count += $changes['count'];
            }
            if (isset($changes['weight'])) {
                $this->current_weight_kg += $changes['weight'];
            }
            if (isset($changes['cost'])) {
                $this->current_cost += $changes['cost'];
            }

            // Ensure no negative values
            $this->current_count = max(0, $this->current_count);
            $this->current_weight_kg = max(0, $this->current_weight_kg);
            $this->current_cost = max(0, $this->current_cost);

            // Recalculate averages
            $this->current_average_weight = $this->current_count > 0
                ? $this->current_weight_kg / $this->current_count
                : 0;
            $this->current_average_cost = $this->current_count > 0
                ? $this->current_cost / $this->current_count
                : 0;

            // Keep remaining_flock in sync (backward compatibility)
            $this->remaining_flock = $this->current_count;

            $this->save();

            // Log the migration
            BatchStateMigration::create([
                'source_batch_id' => $this->id,
                'destination_batch_id' => $destination?->id,
                'migration_type' => $type,
                'count_moved' => $changes['count'] ?? 0,
                'weight_moved' => $changes['weight'] ?? 0,
                'cost_moved' => $changes['cost'] ?? 0,
                'source_state_before' => $sourceBefore,
                'destination_state_before' => $destination ? $destination->getCurrentState() : null,
                'created_by_id' => $user?->id ?? auth()->id(),
            ]);

            // If destination is provided, apply the inverse changes to it
            // (but we do not call updateState again to avoid recursion – we just apply directly)
            if ($destination && $destination->id !== $this->id) {
                $destChanges = [
                    'count' => -($changes['count'] ?? 0),
                    'weight' => -($changes['weight'] ?? 0),
                    'cost' => -($changes['cost'] ?? 0),
                ];
                $destination->applyStateChangeDirectly($destChanges, 'transfer_in', $this, $user);
            }
        });
    }

    /**
     * Direct state update without logging (used for destinations of transfers).
     * Logs as 'transfer_in' separately.
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

            $this->current_count = max(0, $this->current_count);
            $this->current_weight_kg = max(0, $this->current_weight_kg);
            $this->current_cost = max(0, $this->current_cost);

            $this->current_average_weight = $this->current_count > 0
                ? $this->current_weight_kg / $this->current_count
                : 0;
            $this->current_average_cost = $this->current_count > 0
                ? $this->current_cost / $this->current_count
                : 0;

            $this->remaining_flock = $this->current_count;
            $this->save();

            // Log the migration (destination side)
            BatchStateMigration::create([
                'source_batch_id' => $source?->id ?? $this->id,
                'destination_batch_id' => $this->id,
                'migration_type' => $type,
                'count_moved' => $changes['count'] ?? 0,
                'weight_moved' => $changes['weight'] ?? 0,
                'cost_moved' => $changes['cost'] ?? 0,
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
                'source_state_before' => null,
                'destination_state_before' => null,
                'created_by_id' => auth()->id(),
            ]);
        });
    }

    // ---- LEGACY SERVICE METHODS (KEPT FOR BACKWARD COMPATIBILITY) ----
    // Some may be deprecated over time, but we keep them for now.

    public function calculateRequiredSampleSize(): int
    {
        return BatchCalculationService::calculateRequiredSampleSize($this->current_count ?? 0);
    }

    public function calculateTotalInvestment(): float
    {
        return $this->current_cost; // Now directly using the checkpoint
    }

    public function getCurrentAverageWeight(): float
    {
        return (float) $this->current_average_weight;
    }

    public function getDressedWeightPerBird(): float
    {
        $dressPercentage = \App\Models\SystemVariable::getValue('dress_percentage', 75);
        return $this->current_average_weight * ($dressPercentage / 100);
    }

    public function getCostPerBird(): float
    {
        return (float) $this->current_average_cost;
    }

    public function getCostPerKg(): float
    {
        $dressedWeight = $this->getDressedWeightPerBird();
        return $dressedWeight > 0 ? $this->current_average_cost / $dressedWeight : 0;
    }

    public function getSellingPricePerBird(): float
    {
        $profitMargin = \App\Models\SystemVariable::getValue('profit_margin', 20);
        return $this->current_average_cost * (1 + $profitMargin / 100);
    }

    public function getCalculatedSellingPricePerKg(): float
    {
        $dressedWeight = $this->getDressedWeightPerBird();
        $sellingPricePerBird = $this->getSellingPricePerBird();
        return $dressedWeight > 0 ? $sellingPricePerBird / $dressedWeight : 0;
    }

    public function getFinancialMetrics(): array
    {
        $costPerBird = $this->getCostPerBird();
        $costPerKg = $this->getCostPerKg();
        $sellingPricePerBird = $this->getSellingPricePerBird();
        $sellingPricePerKg = $this->getCalculatedSellingPricePerKg();
        $sellingPricePerCarton = $sellingPricePerKg * 10;

        return [
            'cost_per_bird' => round($costPerBird, 2),
            'cost_per_kg' => round($costPerKg, 2),
            'selling_price_per_bird' => round($sellingPricePerBird, 2),
            'selling_price_per_kg' => round($sellingPricePerKg, 2),
            'selling_price_per_carton' => round($sellingPricePerCarton, 2),
            'current_live_weight_kg' => round($this->current_average_weight, 3),
            'current_dressed_weight_kg' => round($this->getDressedWeightPerBird(), 3),
            'profit_margin_percent' => \App\Models\SystemVariable::getValue('profit_margin', 20),
            'dress_percentage' => \App\Models\SystemVariable::getValue('dress_percentage', 75),
            'remaining_flock' => $this->current_count,
        ];
    }

    public function checkSlaughterTriggers(): array
    {
        // Keep as-is from BatchTriggerService
        return BatchTriggerService::checkSlaughterTriggers($this);
    }

    public function allocateCostForSlaughter(int $numberSlaughtered, ?int $oldRemaining = null, ?float $oldTotalInvestment = null): float
    {
        // We no longer need this for the checkpoint approach, but keep for compatibility.
        // It will not be used by the new logic.
        return 0;
    }

    public function updateCachedMetrics()
    {
        // This now does nothing – we keep it for compatibility.
        // All state is maintained via updateState methods.
        // However, we might want to keep it for FCR updates etc.
        // We'll still call it after record additions to update derived fields.
        BatchCalculationService::updateCachedMetrics($this);
    }
}