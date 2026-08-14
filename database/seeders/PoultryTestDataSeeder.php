<?php

namespace Database\Seeders;

use App\Models\Poultry\Batch;
use App\Models\Poultry\Pen;
use App\Models\Poultry\FlockRecord;
use App\Models\Poultry\WeightRecord;
use App\Models\Poultry\FeedRecord;
use App\Models\Poultry\Expense;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PoultryTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Get a pen
        $pen = Pen::where('pen_code', 'pen1')->first();
        if (!$pen) {
            $pen = Pen::create([
                'name' => 'Test Pen',
                'pen_code' => 'testpen',
                'pen_type' => 'batch',
                'capacity' => 1000,
                'is_active' => true,
            ]);
        }

        // Get a user (staff or admin)
        $user = User::where('role', 'staff')->first() ?? User::first();

        // Create a batch
        $batch = Batch::create([
            'batch_id' => 'B-TEST-001',
            'name' => 'Test Batch 2024',
            'start_date' => Carbon::now()->subDays(20),
            'starting_flock' => 500,
            'remaining_flock' => 480,
            'phase' => 'batch',
            'pen_id' => $pen->id,
            'initial_chicken_cost' => 25000,
            'status' => 'active',
            'created_by_id' => $user->id,
            'sector_id' => 1, // poultry
        ]);

        // Flock records
        FlockRecord::create([
            'poultry_batch_id' => $batch->id,
            'date' => Carbon::now()->subDays(10),
            'mortality' => 10,
            'culls' => 5,
            'slaughter' => 0,
            'recorded_by_id' => $user->id,
        ]);

        FlockRecord::create([
            'poultry_batch_id' => $batch->id,
            'date' => Carbon::now()->subDays(5),
            'mortality' => 5,
            'culls' => 0,
            'slaughter' => 0,
            'recorded_by_id' => $user->id,
        ]);

        // Weight records
        $weights = [0.150, 0.160, 0.145, 0.155, 0.170, 0.165, 0.140, 0.180, 0.155, 0.160];
        WeightRecord::create([
            'poultry_batch_id' => $batch->id,
            'date' => Carbon::now()->subDays(7),
            'individual_weights' => $weights,
            'recorded_by_id' => $user->id,
        ]);

        $weights2 = [0.450, 0.460, 0.440, 0.470, 0.455, 0.465, 0.445, 0.480, 0.450, 0.460];
        WeightRecord::create([
            'poultry_batch_id' => $batch->id,
            'date' => Carbon::now()->subDays(3),
            'individual_weights' => $weights2,
            'recorded_by_id' => $user->id,
        ]);

        // Feed records
        FeedRecord::create([
            'poultry_batch_id' => $batch->id,
            'inventory_item_id' => 1, // assumes starter feed exists
            'date' => Carbon::now()->subDays(2),
            'feed_used' => 50,
            'feed_cost_per_kg' => 250,
            'total_feed_cost' => 12500,
            'feed_per_bird' => 0.104,
            'recorded_by_id' => $user->id,
        ]);

        // Expenses
        Expense::create([
            'poultry_batch_id' => $batch->id,
            'date' => Carbon::now()->subDays(5),
            'category' => 'medication',
            'description' => 'Antibiotics',
            'amount' => 1500,
            'receipt_number' => 'RX001',
            'vendor' => 'VetPharm',
            'recorded_by_id' => $user->id,
        ]);

        // Update batch metrics
        $batch->updateCachedMetrics();

        $this->command->info('Test poultry data seeded successfully.');
    }
}