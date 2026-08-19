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
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RealisticFarmSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@bwetfarms.com')->first();
        if (!$admin) {
            $admin = User::first();
        }

        if (!$admin) {
            $this->command->error('No user found. Please run UserSeeder first.');
            return;
        }

        $sector = \App\Models\Sector::where('slug', 'poultry')->first();
        if (!$sector) {
            $this->command->error('Poultry sector not found. Run SectorSeeder first.');
            return;
        }
        $sectorId = $sector->id;

        // Create 5 batches – using firstOrCreate to avoid duplicates
        $batchIds = ['B0011', 'B0012', 'B0013', 'B0014', 'B0015'];
        $batches = collect(); // changed from [] to a Collection so ->random() works below
        foreach ($batchIds as $i => $batchId) {
            $startDate = Carbon::now()->subDays(rand(10, 60));
            $batch = Batch::firstOrCreate(
                ['batch_id' => $batchId],
                [
                    'name' => "Farm Batch " . ($i + 11),
                    'hatchery' => ['Broiler', 'Layer', 'Hybrid'][rand(0, 2)],
                    'start_date' => $startDate,
                    'starting_flock' => rand(2000, 5000),
                    'remaining_flock' => rand(1500, 4800),
                    'phase' => rand(0,1) ? 'brooding' : 'batch',
                    'pen_id' => null,
                    'initial_chicken_cost' => rand(500000, 2000000),
                    'status' => ['active', 'active', 'active', 'closed', 'completed'][rand(0,4)],
                    'created_by_id' => $admin->id,
                    'sector_id' => $sectorId,
                    'current_age_days' => rand(5, 50),
                    'total_mortality' => rand(10, 200),
                    'total_culls' => rand(5, 50),
                    'total_slaughter' => rand(0, 100),
                    'total_feed_used' => rand(1000, 5000),
                    'bags_consumed' => rand(40, 200),
                    'total_weight_gain' => rand(500, 3000),
                    'current_ifcr' => rand(150, 220) / 100,
                    'current_cfcr' => rand(160, 240) / 100,
                    'current_marginal_profit_percent' => rand(-5, 25),
                    'total_expenses' => rand(100000, 500000),
                    'cost_allocated_so_far' => rand(0, 100000),
                    'peak_profit' => rand(100000, 800000),
                    'profit_margin_used' => rand(5, 30),
                    'stop_loss_used_percent' => rand(0, 50),
                    'is_manual_mode' => rand(0,1) ? false : true,
                    'manual_mode_reason' => rand(0,1) ? null : 'Manual intervention required',
                    'manual_mode_enabled_by_id' => rand(0,1) ? $admin->id : null,
                    'manual_mode_enabled_at' => rand(0,1) ? now()->subDays(rand(1,5)) : null,
                ]
            );
            $batches->push($batch); // changed from $batches[] = $batch;
        }

        // For each batch, create records
        foreach ($batches as $batch) {
            for ($j = 1; $j <= 5; $j++) {
                $date = Carbon::now()->subDays(rand(1, 30))->format('Y-m-d');
                FlockRecord::firstOrCreate(
                    [
                        'poultry_batch_id' => $batch->id,
                        'date' => $date,
                    ],
                    [
                        'mortality' => rand(0, 10),
                        'culls' => rand(0, 5),
                        'slaughter' => rand(0, 20),
                        'notes' => rand(0,1) ? 'Routine check' : null,
                        'recorded_by_id' => $admin->id,
                    ]
                );
            }

            // Weight records
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
                    'notes' => rand(0,1) ? 'Good sample' : null,
                    'recorded_by_id' => $admin->id,
                ]);
            }

            // Feed records
            for ($j = 1; $j <= 5; $j++) {
                $feedUsed = rand(50, 500);
                $costPerKg = rand(250, 400) / 100;
                FeedRecord::create([
                    'poultry_batch_id' => $batch->id,
                    'inventory_item_id' => null,
                    'date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d'),
                    'feed_used' => $feedUsed,
                    'feed_cost_per_kg' => $costPerKg,
                    'total_feed_cost' => $feedUsed * $costPerKg,
                    'feed_per_bird' => $feedUsed / max(1, $batch->remaining_flock),
                    'recorded_by_id' => $admin->id,
                ]);
            }

            // Expenses
            $categories = ['medication', 'vaccination', 'labor', 'utilities', 'maintenance', 'transport', 'packaging', 'other'];
            for ($j = 1; $j <= 5; $j++) {
                Expense::create([
                    'poultry_batch_id' => $batch->id,
                    'date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d'),
                    'category' => $categories[array_rand($categories)],
                    'description' => 'Expense ' . ($j + 1),
                    'amount' => rand(1000, 50000),
                    'receipt_number' => 'RCP-' . strtoupper(uniqid()),
                    'vendor' => ['Vendor A', 'Vendor B', 'Vendor C'][rand(0,2)],
                    'recorded_by_id' => $admin->id,
                ]);
            }

            // Performance metric
            PerformanceMetric::firstOrCreate(
                [
                    'poultry_batch_id' => $batch->id,
                    'date' => now()->format('Y-m-d'),
                ],
                [
                    'age_days' => $batch->current_age_days,
                    'average_weight' => rand(1000, 2500) / 1000,
                    'daily_feed' => rand(50, 200),
                    'cumulative_feed' => $batch->total_feed_used,
                    'ifcr' => $batch->current_ifcr,
                    'cfcr' => $batch->current_cfcr,
                    'marginal_profit_percent' => $batch->current_marginal_profit_percent,
                    'adg' => rand(30, 80) / 1000,
                ]
            );
        }

        // Inventory items – use firstOrCreate on name
        $inventoryData = [
            ['name' => 'Starter Feed', 'category' => 'feed', 'unit' => 'kg', 'cost_per_unit' => rand(100, 500)],
            ['name' => 'Finisher Feed', 'category' => 'feed', 'unit' => 'kg', 'cost_per_unit' => rand(100, 500)],
            ['name' => 'Antibiotic', 'category' => 'medicine', 'unit' => 'ml', 'cost_per_unit' => rand(100, 500)],
            ['name' => 'ND Vaccine', 'category' => 'vaccine', 'unit' => 'unit', 'cost_per_unit' => rand(100, 500)],
            ['name' => 'Gloves', 'category' => 'consumables', 'unit' => 'box', 'cost_per_unit' => rand(100, 500)],
        ];
        $inventoryItems = collect(); // Collection so ->random() works below
        foreach ($inventoryData as $data) {
            $item = InventoryItem::firstOrCreate(
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
            $inventoryItems->push($item);
        }

        // Link feed records to inventory items (assign random feed items)
        $feedItems = $inventoryItems->filter(fn($item) => $item->category === 'feed');
        foreach ($batches as $batch) {
            $feedRecords = FeedRecord::where('poultry_batch_id', $batch->id)->get();
            foreach ($feedRecords as $feedRecord) {
                if ($feedItems->isNotEmpty() && $feedRecord->inventory_item_id === null) {
                    $feedItem = $feedItems->random();
                    $feedRecord->inventory_item_id = $feedItem->id;
                    $feedRecord->save();
                }
            }
        }

        // Inventory consumptions
        for ($i = 1; $i <= 5; $i++) {
            $batch = $batches->random();
            $item = $inventoryItems->random();
            $quantity = rand(5, 50);
            InventoryConsumption::create([
                'inventory_item_id' => $item->id,
                'poultry_batch_id' => rand(0,1) ? $batch->id : null,
                'quantity_used' => $quantity,
                'date' => Carbon::now()->subDays(rand(1, 20))->format('Y-m-d'),
                'unit_cost_at_time' => $item->cost_per_unit,
                'total_cost' => $quantity * $item->cost_per_unit,
                'source_type' => ['manual', 'feed', 'expense', 'waste'][rand(0,3)],
                'source_id' => null,
                'recorded_by_id' => $admin->id,
                'reason' => rand(0,1) ? 'Usage' : null,
                'notes' => rand(0,1) ? 'Routine use' : null,
            ]);
        }
    }
}