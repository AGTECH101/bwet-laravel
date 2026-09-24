<?php

namespace Database\Seeders;

use App\Models\Poultry\Batch;
use App\Models\Poultry\FlockRecord;
use App\Models\Poultry\WeightRecord;
use App\Models\Poultry\FeedRecord;
use App\Models\Poultry\Expense;
use App\Models\Poultry\InventoryItem;
use App\Models\Poultry\InventoryConsumption;
use App\Models\Poultry\PerformanceMetric;
use App\Models\BatchStateMigration;
use App\Models\User;
use App\Models\Sector;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RealisticFarmSeeder extends Seeder
{
    public function run(): void
    {
        // Get admin user
        $admin = User::where('email', 'admin@bwetfarms.com')->first();
        if (!$admin) {
            $admin = User::first();
        }

        if (!$admin) {
            $this->command->error('No user found. Please run UserSeeder first.');
            return;
        }

        $sector = Sector::where('slug', 'poultry')->first();
        if (!$sector) {
            $this->command->error('Poultry sector not found. Run SectorSeeder first.');
            return;
        }
        $sectorId = $sector->id;

        // ─── 1. CREATE BATCHES ──────────────────────────────
        $batchIds = collect(range(11, 25))->map(fn ($n) => 'B00' . $n);

        $statusPool = collect(array_fill(0, 5, 'active'))
            ->merge(array_fill(0, 5, 'closed'))
            ->merge(array_fill(0, 5, 'completed'))
            ->shuffle()
            ->values();

        $hatcheryPool = collect(array_fill(0, 5, 'Broiler'))
            ->merge(array_fill(0, 5, 'Layer'))
            ->merge(array_fill(0, 5, 'Hybrid'))
            ->shuffle()
            ->values();

        $batches = collect();

        foreach ($batchIds as $i => $batchId) {
            $status = $statusPool[$i];
            $hatchery = $hatcheryPool[$i];

            $ageDays = match ($status) {
                'active' => rand(3, 40),
                'closed' => rand(10, 35),
                'completed' => rand(42, 58),
            };
            $phase = ($status === 'active' && $ageDays <= 14) ? 'brooding' : 'batch';

            $startingFlock = rand(2000, 5000);

            $mortalityRate = match ($status) {
                'closed' => rand(8, 18) / 100,
                'completed' => rand(2, 6) / 100,
                default => rand(1, 5) / 100,
            };
            $mortality = (int) round($startingFlock * $mortalityRate);
            $culls = rand(10, 80);
            $preSlaughter = max(0, $startingFlock - $mortality - $culls);

            $slaughterRate = match ($status) {
                'completed' => rand(85, 100) / 100,
                'closed' => rand(20, 50) / 100,
                default => rand(0, 15) / 100,
            };
            $slaughter = (int) round($preSlaughter * $slaughterRate);
            $remaining = max(0, $preSlaughter - $slaughter);

            $avgWeight = round(min(3.0, max(0.12, 0.05 + $ageDays * 0.045 + (rand(-4, 4) / 100))), 3);

            $fcr = round(rand(155, 225) / 100, 2);
            $totalFeedUsed = round(max($remaining, 1) * $avgWeight * $fcr, 1);
            $feedCostPerKg = round(rand(250, 400) / 100, 2);
            $initialChickenCost = $startingFlock * rand(250, 400);
            $currentCost = round($totalFeedUsed * $feedCostPerKg + $initialChickenCost + rand(20000, 120000), 2);
            $currentWeight = round($remaining * $avgWeight, 3);

            $batch = Batch::firstOrCreate(
                ['batch_id' => $batchId],
                [
                    'name' => "Farm Batch " . (11 + $i),
                    'hatchery' => $hatchery,
                    'start_date' => Carbon::now()->subDays($ageDays),
                    'starting_flock' => $startingFlock,
                    'remaining_flock' => $remaining,
                    'phase' => $phase,
                    'pen_id' => null,
                    'initial_chicken_cost' => $initialChickenCost,
                    'status' => $status,
                    'created_by_id' => $admin->id,
                    'sector_id' => $sectorId,
                    'current_age_days' => $ageDays,
                    'total_mortality' => $mortality,
                    'total_culls' => $culls,
                    'total_slaughter' => $slaughter,
                    'total_feed_used' => $totalFeedUsed,
                    'bags_consumed' => round($totalFeedUsed / 25, 1),
                    'total_weight_gain' => $currentWeight,
                    'current_ifcr' => $fcr,
                    'current_cfcr' => round($fcr + (rand(5, 20) / 100), 2),
                    'current_marginal_profit_percent' => $status === 'closed' ? rand(-15, 5) : rand(5, 25),
                    'total_expenses' => rand(100000, 500000),
                    'cost_allocated_so_far' => rand(0, 100000),
                    'peak_profit' => rand(100000, 800000),
                    'profit_margin_used' => rand(5, 30),
                    'stop_loss_used_percent' => $status === 'closed' ? rand(30, 80) : rand(0, 20),
                    'is_manual_mode' => $status === 'closed',
                    'manual_mode_reason' => $status === 'closed' ? 'Closed early — health/performance issue' : null,
                    'manual_mode_enabled_by_id' => $status === 'closed' ? $admin->id : null,
                    'manual_mode_enabled_at' => $status === 'closed' ? now()->subDays(rand(1, max($ageDays, 1))) : null,
                    'current_count' => $remaining,
                    'current_weight_kg' => $currentWeight,
                    'current_cost' => $currentCost,
                    'current_average_weight' => $avgWeight,
                    'current_average_cost' => round($currentCost / max($remaining, 1), 2),
                ]
            );
            $batches->push($batch);
        }

        // ─── 2. CREATE FLOCK RECORDS ──────────────────────────────
        foreach ($batches as $batch) {
            $flockDayOffsets = collect(range(1, 30))->shuffle()->take(5);
            foreach ($flockDayOffsets as $daysAgo) {
                $date = Carbon::now()->subDays($daysAgo)->format('Y-m-d');

                $exists = FlockRecord::where('poultry_batch_id', $batch->id)
                    ->whereDate('date', $date)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $mortality = rand(0, 10);
                $culls = rand(0, 5);
                $slaughter = rand(0, 20);

                FlockRecord::create([
                    'poultry_batch_id' => $batch->id,
                    'date' => $date,
                    'mortality' => $mortality,
                    'culls' => $culls,
                    'slaughter' => $slaughter,
                    'notes' => rand(0, 1) ? 'Routine check' : null,
                    'recorded_by_id' => $admin->id,
                    'allocated_cost' => $slaughter > 0 ? rand(1000, 50000) : 0,
                ]);
            }

            $totals = FlockRecord::where('poultry_batch_id', $batch->id)
                ->selectRaw('COALESCE(SUM(mortality), 0) as mortality, COALESCE(SUM(culls), 0) as culls, COALESCE(SUM(slaughter), 0) as slaughter')
                ->first();

            $batch->total_mortality = (int) $totals->mortality;
            $batch->total_culls = (int) $totals->culls;
            $batch->total_slaughter = (int) $totals->slaughter;
            $batch->remaining_flock = $batch->starting_flock - $batch->total_mortality - $batch->total_culls - $batch->total_slaughter;
            $batch->current_count = $batch->remaining_flock;
            $batch->save();
        }

        foreach ($batches as $batch) {
            $totalMort = $batch->flockRecords()->sum('mortality') ?? 0;
            $batch->total_mortality = $totalMort;
            $batch->historical_mortality = $totalMort;
            $batch->pen_mortality = $totalMort;
            $batch->mortality_rate = $batch->starting_flock > 0 ? ($totalMort / $batch->starting_flock) * 100 : 0;
            $batch->save();
        }

        // ─── 3. CREATE WEIGHT RECORDS ──────────────────────────────
        foreach ($batches as $batch) {
            for ($j = 1; $j <= 5; $j++) {
                $weights = [];
                for ($k = 1; $k <= rand(5, 10); $k++) {
                    $weights[] = round(rand(500, 2500) / 1000, 3);
                }
                $avg = array_sum($weights) / count($weights);
                $cv = rand(5, 20) / 100;
                $cvStatus = $cv < 0.10 ? 'excellent' : ($cv < 0.12 ? 'caution' : ($cv < 0.15 ? 'warning' : 'rejected'));
                $isValid = $cvStatus !== 'rejected';

                WeightRecord::create([
                    'poultry_batch_id' => $batch->id,
                    'date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d'),
                    'individual_weights' => $weights,
                    'birds_weighed' => count($weights),
                    'total_weight' => array_sum($weights),
                    'average_weight' => $avg,
                    'coefficient_variation' => $cv * 100,
                    'cv_status' => $cvStatus,
                    'is_valid_sample' => $isValid,
                    'expected_weight' => round(rand(1000, 2500) / 1000, 3),
                    'notes' => rand(0, 1) ? 'Good sample' : null,
                    'recorded_by_id' => $admin->id,
                ]);

                if ($j == 5) {
                    $batch->current_average_weight = $avg;
                    $batch->current_weight_kg = $batch->current_count * $avg;
                    $batch->save();
                }
            }
        }

        // ─── 4. CREATE FEED RECORDS ──────────────────────────────
        $feedItems = InventoryItem::where('category', 'feed')->get();
        if ($feedItems->isEmpty()) {
            $feedItems = collect();
            $feedData = [
                ['name' => 'Starter Feed', 'category' => 'feed', 'unit' => 'kg', 'cost_per_unit' => rand(250, 400) / 100],
                ['name' => 'Finisher Feed', 'category' => 'feed', 'unit' => 'kg', 'cost_per_unit' => rand(280, 450) / 100],
            ];
            foreach ($feedData as $data) {
                $item = InventoryItem::firstOrCreate(
                    ['name' => $data['name']],
                    [
                        'category' => $data['category'],
                        'unit' => $data['unit'],
                        'quantity_in_stock' => rand(500, 1000),
                        'quantity_used' => rand(100, 400),
                        'minimum_quantity' => rand(50, 150),
                        'vendor' => 'Vendor ' . substr($data['name'], 0, 1),
                        'cost_per_unit' => $data['cost_per_unit'],
                        'is_active' => true,
                        'status' => 'active',
                        'created_by_id' => $admin->id,
                    ]
                );
                $feedItems->push($item);
            }
        }

        foreach ($batches as $batch) {
            $totalFeedUsed = 0;
            for ($j = 1; $j <= 5; $j++) {
                $feedUsed = rand(50, 500);
                $totalFeedUsed += $feedUsed;
                $item = $feedItems->random();
                $costPerKg = $item->cost_per_unit;

                FeedRecord::create([
                    'poultry_batch_id' => $batch->id,
                    'inventory_item_id' => $item->id,
                    'date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d'),
                    'feed_used' => $feedUsed,
                    'feed_cost_per_kg' => $costPerKg,
                    'total_feed_cost' => $feedUsed * $costPerKg,
                    'feed_per_bird' => $feedUsed / max(1, $batch->remaining_flock),
                    'recorded_by_id' => $admin->id,
                ]);

                $item->quantity_in_stock = max(0, $item->quantity_in_stock - $feedUsed);
                $item->quantity_used += $feedUsed;
                $item->save();

                InventoryConsumption::create([
                    'inventory_item_id' => $item->id,
                    'poultry_batch_id' => $batch->id,
                    'quantity_used' => $feedUsed,
                    'date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d'),
                    'unit_cost_at_time' => $costPerKg,
                    'total_cost' => $feedUsed * $costPerKg,
                    'source_type' => 'feed',
                    'source_id' => null,
                    'recorded_by_id' => $admin->id,
                ]);
            }

            $batch->total_feed_used = $totalFeedUsed;
            $batch->bags_consumed = $totalFeedUsed / 25;
            $batch->save();
        }

        // ─── 5. CREATE EXPENSES ──────────────────────────────
        $categories = ['medication', 'vaccination', 'labor', 'utilities', 'maintenance', 'transport', 'packaging', 'other'];
        $expenseCounter = 0;
        foreach ($batches as $batch) {
            $totalExpenses = 0;
            for ($j = 1; $j <= 5; $j++) {
                $amount = rand(1000, 50000);
                $totalExpenses += $amount;
                Expense::create([
                    'poultry_batch_id' => $batch->id,
                    'date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d'),
                    'category' => $categories[$expenseCounter % count($categories)],
                    'description' => 'Expense ' . ($j + 1),
                    'amount' => $amount,
                    'receipt_number' => 'RCP-' . strtoupper(uniqid()),
                    'vendor' => ['Vendor A', 'Vendor B', 'Vendor C'][rand(0, 2)],
                    'recorded_by_id' => $admin->id,
                ]);
                $expenseCounter++;
            }
            $batch->total_expenses = $totalExpenses;
            $batch->save();
        }

        // ─── 6. CREATE PERFORMANCE METRICS ──────────────────────────────
        foreach ($batches as $batch) {
            $today = now()->format('Y-m-d');

            $exists = PerformanceMetric::where('poultry_batch_id', $batch->id)
                ->whereDate('date', $today)
                ->exists();

            if ($exists) {
                continue;
            }

            PerformanceMetric::create([
                'poultry_batch_id' => $batch->id,
                'date' => $today,
                'age_days' => $batch->current_age_days,
                'average_weight' => $batch->current_average_weight,
                'daily_feed' => rand(50, 200),
                'cumulative_feed' => $batch->total_feed_used,
                'ifcr' => $batch->current_ifcr,
                'cfcr' => $batch->current_cfcr,
                'marginal_profit_percent' => $batch->current_marginal_profit_percent,
                'adg' => rand(30, 80) / 1000,
            ]);
        }

        // ─── 7. CREATE BATCH STATE MIGRATIONS (Transfers) ──────────────
        //
        // Sign convention (must match BatchTransferController@store and
        // BatchRecalculationService):
        //   transfer_out : all metrics are NEGATIVE
        //   transfer_in  : all metrics are POSITIVE
        // Source/destination IDs stay the same on BOTH rows — the direction
        // is encoded by migration_type, not by swapping the batch IDs.
        // The transfer_in row stores its paired transfer_out row's id in
        // source_id so the admin edit screen can find both halves.
        if (Schema::hasTable('batch_state_migrations')) {
            $activeBatches = $batches->where('status', 'active')->values();

            for ($p = 0; $p < $activeBatches->count() - 1; $p++) {
                $source = $activeBatches[$p];
                $destination = $activeBatches[$p + 1];
                $transferCount = rand(50, 200);

                if ($transferCount > $source->current_count) {
                    continue;
                }

                $transferWeight = $transferCount * $source->current_average_weight;
                $transferCost = $transferCount * $source->current_average_cost;

                $sourceBefore = $source->getCurrentState();
                $destBefore = $destination->getCurrentState();

                // Update source
                $source->current_count -= $transferCount;
                $source->current_weight_kg -= $transferWeight;
                $source->current_cost -= $transferCost;
                $source->current_average_weight = $source->current_count > 0
                    ? $source->current_weight_kg / $source->current_count
                    : 0;
                $source->current_average_cost = $source->current_count > 0
                    ? $source->current_cost / $source->current_count
                    : 0;
                $source->remaining_flock = $source->current_count;
                $source->save();

                // Update destination. Bump starting_flock so mortality rate
                // for the destination remains a meaningful percentage of the
                // total flock it has ever held.
                $destination->starting_flock += $transferCount;
                $destination->current_count += $transferCount;
                $destination->current_weight_kg += $transferWeight;
                $destination->current_cost += $transferCost;
                $destination->current_average_weight = $destination->current_count > 0
                    ? $destination->current_weight_kg / $destination->current_count
                    : 0;
                $destination->current_average_cost = $destination->current_count > 0
                    ? $destination->current_cost / $destination->current_count
                    : 0;
                $destination->remaining_flock = $destination->current_count;
                $destination->save();

                // Log the transfer_out row
                $transferOut = BatchStateMigration::create([
                    'source_batch_id' => $source->id,
                    'destination_batch_id' => $destination->id,
                    'migration_type' => 'transfer_out',
                    'source_type' => 'batch_transfer',
                    'count_moved' => -$transferCount,
                    'weight_moved' => -$transferWeight,
                    'cost_moved' => -$transferCost,
                    'mortality_moved' => 0,
                    'feed_moved' => 0,
                    'weight_gain_moved' => 0,
                    'source_state_before' => $sourceBefore,
                    'destination_state_before' => $destBefore,
                    'created_by_id' => $admin->id,
                ]);

                // Log the transfer_in row (same source/destination IDs, positive values)
                BatchStateMigration::create([
                    'source_batch_id' => $source->id,
                    'destination_batch_id' => $destination->id,
                    'migration_type' => 'transfer_in',
                    'source_type' => 'batch_transfer',
                    'source_id' => $transferOut->id,
                    'count_moved' => $transferCount,
                    'weight_moved' => $transferWeight,
                    'cost_moved' => $transferCost,
                    'mortality_moved' => 0,
                    'feed_moved' => 0,
                    'weight_gain_moved' => 0,
                    'source_state_before' => $sourceBefore,
                    'destination_state_before' => $destBefore,
                    'created_by_id' => $admin->id,
                ]);
            }
        }

        // ─── 8. CREATE ADDITIONAL INVENTORY ITEMS ──────────────────────────────
        $inventoryData = [
            ['name' => 'Starter Feed', 'category' => 'feed', 'unit' => 'kg', 'cost_per_unit' => rand(100, 500)],
            ['name' => 'Finisher Feed', 'category' => 'feed', 'unit' => 'kg', 'cost_per_unit' => rand(100, 500)],
            ['name' => 'Antibiotic', 'category' => 'medicine', 'unit' => 'ml', 'cost_per_unit' => rand(100, 500)],
            ['name' => 'ND Vaccine', 'category' => 'vaccine', 'unit' => 'unit', 'cost_per_unit' => rand(100, 500)],
            ['name' => 'Gloves', 'category' => 'consumables', 'unit' => 'box', 'cost_per_unit' => rand(100, 500)],
            ['name' => 'Syringes', 'category' => 'consumables', 'unit' => 'unit', 'cost_per_unit' => rand(50, 200)],
            ['name' => 'Disinfectant', 'category' => 'other', 'unit' => 'l', 'cost_per_unit' => rand(150, 300)],
            ['name' => 'Packaging Bags', 'category' => 'packaging', 'unit' => 'box', 'cost_per_unit' => rand(200, 400)],
        ];

        foreach ($inventoryData as $data) {
            InventoryItem::firstOrCreate(
                ['name' => $data['name']],
                [
                    'category' => $data['category'],
                    'unit' => $data['unit'],
                    'quantity_in_stock' => rand(100, 1000),
                    'quantity_used' => rand(10, 500),
                    'minimum_quantity' => rand(10, 100),
                    'vendor' => 'Vendor ' . substr($data['name'], 0, 1),
                    'cost_per_unit' => $data['cost_per_unit'],
                    'is_active' => true,
                    'status' => 'active',
                    'created_by_id' => $admin->id,
                ]
            );
        }

        // ─── 9. CREATE MISCELLANEOUS INVENTORY CONSUMPTIONS ──────────────────────────────
        $allItems = InventoryItem::all();
        $sourceTypes = ['manual', 'feed', 'expense', 'waste'];
        for ($i = 0; $i < 24; $i++) {
            $batch = $batches->random();
            $item = $allItems->random();
            $quantity = rand(5, 50);
            InventoryConsumption::create([
                'inventory_item_id' => $item->id,
                'poultry_batch_id' => rand(0, 1) ? $batch->id : null,
                'quantity_used' => $quantity,
                'date' => Carbon::now()->subDays(rand(1, 20))->format('Y-m-d'),
                'unit_cost_at_time' => $item->cost_per_unit,
                'total_cost' => $quantity * $item->cost_per_unit,
                'source_type' => $sourceTypes[$i % count($sourceTypes)],
                'source_id' => null,
                'recorded_by_id' => $admin->id,
                'reason' => rand(0, 1) ? 'Usage' : null,
                'notes' => rand(0, 1) ? 'Routine use' : null,
            ]);
        }

        $this->command->info('✅ Poultry data seeded successfully!');
    }
}