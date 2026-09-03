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
        // 15 batches guarantee at least 5 records for every key category
        // (status: active/closed/completed, hatchery: Broiler/Layer/Hybrid)
        // instead of leaving the distribution to chance. Age, phase,
        // mortality, slaughter, weight, and cost are then derived from
        // status/age rather than drawn independently, so the data tells a
        // coherent story instead of being unrelated random numbers.
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

            // Age correlates with status: a batch can't be "completed" at
            // 10 days old, and "closed" batches are closed early (before a
            // full ~42-day broiler cycle), typically due to a problem.
            $ageDays = match ($status) {
                'active' => rand(3, 40),
                'closed' => rand(10, 35),
                'completed' => rand(42, 58),
            };
            $phase = ($status === 'active' && $ageDays <= 14) ? 'brooding' : 'batch';

            $startingFlock = rand(2000, 5000);

            // Mortality rate correlates with status — batches closed early
            // usually had a health/performance issue driving that closure.
            $mortalityRate = match ($status) {
                'closed' => rand(8, 18) / 100,
                'completed' => rand(2, 6) / 100,
                default => rand(1, 5) / 100,
            };
            $mortality = (int) round($startingFlock * $mortalityRate);
            $culls = rand(10, 80);
            $preSlaughter = max(0, $startingFlock - $mortality - $culls);

            // Slaughter/sales correlate with status: completed batches have
            // sold through almost their whole flock; active/closed ones
            // mostly haven't.
            $slaughterRate = match ($status) {
                'completed' => rand(85, 100) / 100,
                'closed' => rand(20, 50) / 100,
                default => rand(0, 15) / 100,
            };
            $slaughter = (int) round($preSlaughter * $slaughterRate);
            $remaining = max(0, $preSlaughter - $slaughter);

            // Weight follows a simple broiler growth curve keyed to age
            // (instead of an independent random draw), so age, weight, and
            // feed/cost below all move together the way they would on a
            // real farm.
            $avgWeight = round(min(3.0, max(0.12, 0.05 + $ageDays * 0.045 + (rand(-4, 4) / 100))), 3);

            $fcr = round(rand(155, 225) / 100, 2); // feed:gain ratio
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
                    // ─── CHECKPOINT COLUMNS ──────────────
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
            // Draw 5 distinct day-offsets (not 5 independent rand() calls) so we
            // never generate the same date twice for this batch in one run —
            // flock_records has a unique constraint on (poultry_batch_id, date).
            $flockDayOffsets = collect(range(1, 30))->shuffle()->take(5);
            foreach ($flockDayOffsets as $daysAgo) {
                $date = Carbon::now()->subDays($daysAgo)->format('Y-m-d');

                // firstOrCreate() can't be trusted here across separate seeder
                // runs: the stored `date` comes back as a full datetime string
                // (e.g. "2026-08-22 00:00:00") while this bare "Y-m-d" string
                // won't match it exactly, so firstOrCreate's lookup misses an
                // already-seeded row and tries to insert a duplicate. whereDate()
                // compares the calendar date only, so it correctly finds rows
                // seeded in a previous run and skips re-creating them.
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

            // Recompute batch totals from the actual stored flock records
            // (source of truth) rather than an in-memory running counter —
            // that counter would be wrong on any rerun where some dates were
            // skipped above because they already existed.
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

        // After creating flock records for each batch, set mortality fields
        foreach ($batches as $batch) {
            // Recalculate total_mortality from flock records
            $totalMort = $batch->flockRecords()->sum('mortality') ?? 0;
            $batch->total_mortality = $totalMort;
            $batch->historical_mortality = $totalMort;  // all deaths are historical initially
            $batch->pond_mortality = $totalMort;        // all deaths happened in this pond initially
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

                // Update batch average weight from last record
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
            // Create feed items if none exist
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

                // Update inventory stock (simulate consumption)
                $item->quantity_in_stock = max(0, $item->quantity_in_stock - $feedUsed);
                $item->quantity_used += $feedUsed;
                $item->save();

                // Create inventory consumption record
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

            // Update batch feed totals
            $batch->total_feed_used = $totalFeedUsed;
            $batch->bags_consumed = $totalFeedUsed / 25;
            $batch->save();
        }

        // ─── 5. CREATE EXPENSES ──────────────────────────────
        // Cycle through categories with a running counter instead of
        // array_rand() so every category is guaranteed to appear multiple
        // times across the 75 records generated below (15 batches × 5),
        // rather than leaving coverage to chance.
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

            // Same firstOrCreate date-mismatch issue as FlockRecord above —
            // use whereDate() to reliably detect an already-seeded row.
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
        // With 5 active batches now guaranteed, transfer between every
        // consecutive pair (sorting-by-size style) so the migration/
        // checkpoint model has several varied transfers to work with,
        // instead of a single pair.
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

                // Update destination
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

                // Log the migration (source side)
                BatchStateMigration::create([
                    'source_batch_id' => $source->id,
                    'destination_batch_id' => $destination->id,
                    'migration_type' => 'transfer_out',
                    'count_moved' => $transferCount,
                    'weight_moved' => $transferWeight,
                    'cost_moved' => $transferCost,
                    'source_state_before' => $sourceBefore,
                    'destination_state_before' => $destBefore,
                    'created_by_id' => $admin->id,
                ]);

                // Log the migration (destination side)
                BatchStateMigration::create([
                    'source_batch_id' => $destination->id,
                    'destination_batch_id' => $source->id,
                    'migration_type' => 'transfer_in',
                    'count_moved' => $transferCount,
                    'weight_moved' => $transferWeight,
                    'cost_moved' => $transferCost,
                    'source_state_before' => $destBefore,
                    'destination_state_before' => $sourceBefore,
                    'created_by_id' => $admin->id,
                ]);
            }
        }

        // ─── 8. CREATE ADDITIONAL INVENTORY ITEMS ──────────────────────────────
        // (if they don't already exist – we create a wide variety)
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
        // 24 records, cycling source_type deterministically, guarantees all
        // 4 source types appear at least 6 times each (24 / 4) instead of
        // leaving it to random chance over only 10 draws.
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