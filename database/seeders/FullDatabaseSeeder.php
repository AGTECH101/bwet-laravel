<?php

namespace Database\Seeders;

use App\Models\Sector;
use App\Models\User;
use App\Models\Poultry\Pen;
use App\Models\Poultry\Batch;
use App\Models\Poultry\FlockRecord;
use App\Models\Poultry\WeightRecord;
use App\Models\Poultry\FeedRecord;
use App\Models\Poultry\Expense;
use App\Models\Poultry\WeighingSchedule;
use App\Models\Poultry\PerformanceMetric;
use App\Models\Poultry\InventoryItem;
use App\Models\Poultry\InventoryConsumption;
use App\Models\Poultry\InvestorInvestment;
use App\Models\ObservationCategory;
use App\Models\ObservationReport;
use App\Models\HistoryQuery;
use App\Models\Notification;
use App\Models\NotificationReadStatus;
use App\Models\ExportHistory;
use App\Models\SystemVariable;
use App\Models\MarketPrice;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class FullDatabaseSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // 1. Ensure sector exists
        $sector = Sector::firstOrCreate(
            ['slug' => 'poultry'],
            [
                'name' => 'Poultry',
                'description' => 'Broiler and layer production',
                'status' => 'active',
                'is_live' => true,
                'cost' => 0,
                'estimated_revenue' => null,
                'date_of_resumption' => null,
            ]
        );

        // 2. Ensure roles exist
        $roles = ['admin', 'manager', 'staff', 'investor'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // 3. Create users (at least 5) with different roles
        $users = [];
        foreach (['admin', 'manager', 'staff', 'investor', 'staff'] as $role) {
            $user = User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => bcrypt('password'),
                'role' => $role,
                'phone' => $faker->phoneNumber,
                'farm_location' => $faker->city,
                'is_approved' => true,
                'approved_by_id' => null,
                'approved_at' => $faker->dateTimeThisYear,
            ]);
            $user->assignRole($role);
            $users[] = $user;
        }
        while (count($users) < 5) {
            $role = $faker->randomElement(['staff', 'manager']);
            $user = User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => bcrypt('password'),
                'role' => $role,
                'phone' => $faker->phoneNumber,
                'farm_location' => $faker->city,
                'is_approved' => true,
                'approved_by_id' => null,
                'approved_at' => $faker->dateTimeThisYear,
            ]);
            $user->assignRole($role);
            $users[] = $user;
        }

        // 4. Create pens
        $pens = [];
        for ($i = 0; $i < 5; $i++) {
            $pen = Pen::create([
                'name' => $faker->word . ' Pen ' . $i,
                'pen_code' => strtoupper($faker->unique()->lexify('PEN???')),
                'pen_type' => $faker->randomElement(['brooding', 'batch']),
                'capacity' => $faker->numberBetween(500, 5000),
                'is_active' => true,
                'notes' => $faker->sentence,
                'current_batch_id' => null,
                'created_by_id' => $users[0]->id,
            ]);
            $pens[] = $pen;
        }

        // 5. System variables
        $defaultVars = [
            ['name' => 'Profit Margin', 'key' => 'profit_margin', 'category' => 'financial', 'value' => '20', 'data_type' => 'percentage'],
            ['name' => 'Dress Percentage', 'key' => 'dress_percentage', 'category' => 'financial', 'value' => '75', 'data_type' => 'percentage'],
            ['name' => 'Weighing Frequency (Days)', 'key' => 'weighing_frequency_days', 'category' => 'weighing', 'value' => '4', 'data_type' => 'integer'],
            ['name' => 'Daily Profit Tolerance (%)', 'key' => 'daily_profit_tolerance', 'category' => 'performance', 'value' => '-15', 'data_type' => 'percentage'],
            ['name' => 'FCR Efficiency Tolerance (%)', 'key' => 'fcr_efficiency_tolerance', 'category' => 'performance', 'value' => '20', 'data_type' => 'percentage'],
            ['name' => 'Stop Loss Amount (₦)', 'key' => 'stop_loss_amount', 'category' => 'financial', 'value' => '20000', 'data_type' => 'decimal'],
        ];
        foreach ($defaultVars as $var) {
            SystemVariable::firstOrCreate(['key' => $var['key']], $var);
        }

        // 6. Market prices
        for ($i = 0; $i < 5; $i++) {
            MarketPrice::create([
                'price_per_bird' => $faker->randomFloat(2, 1000, 5000),
                'price_per_kg' => $faker->randomFloat(2, 500, 2000),
                'price_per_carton' => $faker->randomFloat(2, 5000, 15000),
                'effective_date' => $faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                'notes' => $faker->sentence,
                'is_active' => $faker->boolean(80),
                'set_by_id' => $users[0]->id,
            ]);
        }

        // 7. Inventory items - ensure we have at least 5 by using firstOrCreate with unique name
        $inventoryNames = ['Starter Feed', 'Grower Feed', 'Finisher Feed', 'Vaccine - ND', 'Antibiotic'];
        $inventoryItems = [];
        $categories = ['feed', 'feed', 'feed', 'vaccine', 'medicine'];
        $units = ['kg', 'kg', 'kg', 'ml', 'ml'];
        foreach ($inventoryNames as $index => $name) {
            $item = InventoryItem::firstOrCreate(
                ['name' => $name],
                [
                    'category' => $categories[$index],
                    'unit' => $units[$index],
                    'quantity_in_stock' => $faker->randomFloat(2, 10, 1000),
                    'quantity_used' => $faker->randomFloat(2, 0, 500),
                    'minimum_quantity' => $faker->randomFloat(2, 5, 50),
                    'vendor' => $faker->company,
                    'cost_per_unit' => $faker->randomFloat(2, 100, 5000),
                    'is_active' => true,
                    'status' => 'active',
                    'created_by_id' => $users[0]->id,
                ]
            );
            $inventoryItems[] = $item;
        }

        // Ensure we have at least 5 items; if not, create additional random ones
        while (count($inventoryItems) < 5) {
            $item = InventoryItem::firstOrCreate(
                ['name' => $faker->unique()->word . ' ' . $faker->randomElement(['supplement', 'additive'])],
                [
                    'category' => $faker->randomElement(['feed', 'medicine', 'consumables']),
                    'unit' => $faker->randomElement(['kg', 'g', 'l', 'ml', 'unit', 'bag', 'box']),
                    'quantity_in_stock' => $faker->randomFloat(2, 10, 1000),
                    'quantity_used' => $faker->randomFloat(2, 0, 500),
                    'minimum_quantity' => $faker->randomFloat(2, 5, 50),
                    'vendor' => $faker->company,
                    'cost_per_unit' => $faker->randomFloat(2, 100, 5000),
                    'is_active' => true,
                    'status' => 'active',
                    'created_by_id' => $users[0]->id,
                ]
            );
            $inventoryItems[] = $item;
        }

        // 8. Batches
        $batches = [];
        for ($i = 0; $i < 5; $i++) {
            $startDate = $faker->dateTimeBetween('-6 months', 'now');
            $manualUser = $faker->boolean(50) ? $faker->randomElement($users) : null;

            $batch = Batch::create([
                'batch_id' => strtoupper($faker->unique()->lexify('BATCH???')),
                'name' => $faker->word . ' Batch ' . ($i+1),
                'hatchery' => $faker->company,
                'start_date' => $startDate->format('Y-m-d'),
                'starting_flock' => $faker->numberBetween(500, 3000),
                'remaining_flock' => $faker->numberBetween(400, 2800),
                'phase' => $faker->randomElement(['brooding', 'batch']),
                'pen_id' => $pens[$i % count($pens)]->id,
                'selling_price_per_kg' => $faker->randomFloat(2, 500, 1500),
                'selling_price_per_carton' => $faker->randomFloat(2, 5000, 12000),
                'initial_chicken_cost' => $faker->randomFloat(2, 20000, 100000),
                'status' => $faker->randomElement(['active', 'closed', 'completed']),
                'closed_at' => $faker->optional(0.3)->dateTimeBetween($startDate, 'now'),
                'current_age_days' => $faker->numberBetween(10, 60),
                'total_mortality' => $faker->numberBetween(0, 100),
                'total_culls' => $faker->numberBetween(0, 50),
                'total_slaughter' => $faker->numberBetween(0, 200),
                'total_feed_used' => $faker->randomFloat(3, 100, 5000),
                'bags_consumed' => $faker->randomFloat(2, 10, 200),
                'total_weight_gain' => $faker->randomFloat(3, 100, 5000),
                'current_ifcr' => $faker->randomFloat(4, 1.5, 2.5),
                'current_cfcr' => $faker->randomFloat(4, 1.8, 3.0),
                'current_marginal_profit_percent' => $faker->randomFloat(2, -10, 30),
                'total_expenses' => $faker->randomFloat(2, 50000, 500000),
                'cost_allocated_so_far' => $faker->randomFloat(2, 20000, 300000),
                'peak_profit' => $faker->randomFloat(2, 10000, 100000),
                'profit_margin_used' => $faker->randomFloat(2, 10, 30),
                'stop_loss_used_percent' => $faker->randomFloat(2, 5, 15),
                'is_manual_mode' => $faker->boolean(10),
                'manual_mode_reason' => $faker->optional(0.5)->sentence,
                'manual_mode_enabled_by_id' => $manualUser ? $manualUser->id : null,
                'manual_mode_enabled_at' => $manualUser ? $faker->dateTimeBetween('-1 month', 'now') : null,
                'created_by_id' => $users[0]->id,
                'sector_id' => $sector->id,
            ]);
            $batches[] = $batch;
        }

        // 9. Dependent records per batch
        foreach ($batches as $batch) {
            // --- Flock records (3 unique dates) using firstOrCreate ---
            $flockDates = [];
            while (count($flockDates) < 3) {
                $date = $faker->dateTimeBetween($batch->start_date, 'now')->format('Y-m-d');
                if (!in_array($date, $flockDates)) {
                    $flockDates[] = $date;
                }
            }
            foreach ($flockDates as $date) {
                FlockRecord::firstOrCreate(
                    ['poultry_batch_id' => $batch->id, 'date' => $date],
                    [
                        'mortality' => $faker->numberBetween(0, 5),
                        'culls' => $faker->numberBetween(0, 3),
                        'slaughter' => $faker->numberBetween(0, 10),
                        'notes' => $faker->sentence,
                        'recorded_by_id' => $users[array_rand($users)]->id,
                    ]
                );
            }

            // --- Weight records ---
            for ($i = 0; $i < 3; $i++) {
                $numWeights = $faker->numberBetween(5, 15);
                $weights = [];
                for ($j = 0; $j < $numWeights; $j++) {
                    $weights[] = $faker->randomFloat(3, 0.1, 2.5);
                }
                WeightRecord::create([
                    'poultry_batch_id' => $batch->id,
                    'date' => $faker->dateTimeBetween($batch->start_date, 'now')->format('Y-m-d'),
                    'individual_weights' => $weights,
                    'birds_weighed' => $numWeights,
                    'total_weight' => array_sum($weights),
                    'average_weight' => array_sum($weights) / $numWeights,
                    'coefficient_variation' => $faker->randomFloat(2, 1, 20),
                    'cv_status' => $faker->randomElement(['excellent', 'caution', 'warning', 'rejected']),
                    'is_valid_sample' => $faker->boolean(80),
                    'expected_weight' => $faker->randomFloat(3, 0.5, 2.0),
                    'notes' => $faker->sentence,
                    'recorded_by_id' => $users[array_rand($users)]->id,
                ]);
            }

            // --- Feed records ---
            // Ensure we have at least one inventory item; if none, fallback to null
            $inventoryItem = $faker->randomElement($inventoryItems);
            for ($i = 0; $i < 3; $i++) {
                $feedUsed = $faker->randomFloat(3, 10, 500);
                $costPerKg = $faker->randomFloat(2, 200, 500);
                FeedRecord::create([
                    'poultry_batch_id' => $batch->id,
                    'inventory_item_id' => $inventoryItem ? $inventoryItem->id : null,
                    'date' => $faker->dateTimeBetween($batch->start_date, 'now')->format('Y-m-d'),
                    'feed_used' => $feedUsed,
                    'feed_cost_per_kg' => $costPerKg,
                    'total_feed_cost' => $feedUsed * $costPerKg,
                    'feed_per_bird' => $feedUsed / max(1, $batch->starting_flock),
                    'recorded_by_id' => $users[array_rand($users)]->id,
                ]);
            }

            // --- Expenses ---
            $expenseCategories = ['medication', 'vaccination', 'labor', 'utilities', 'maintenance', 'transport', 'packaging', 'other'];
            for ($i = 0; $i < 3; $i++) {
                Expense::create([
                    'poultry_batch_id' => $batch->id,
                    'date' => $faker->dateTimeBetween($batch->start_date, 'now')->format('Y-m-d'),
                    'category' => $faker->randomElement($expenseCategories),
                    'description' => $faker->sentence,
                    'amount' => $faker->randomFloat(2, 500, 50000),
                    'receipt_number' => $faker->optional(0.5)->bothify('REC-####'),
                    'vendor' => $faker->company,
                    'recorded_by_id' => $users[array_rand($users)]->id,
                ]);
            }

            // --- Weighing schedules ---
            for ($i = 0; $i < 2; $i++) {
                WeighingSchedule::create([
                    'poultry_batch_id' => $batch->id,
                    'scheduled_date' => $faker->dateTimeBetween($batch->start_date, 'now')->format('Y-m-d'),
                    'is_completed' => $faker->boolean(60),
                    'completed_at' => $faker->optional(0.6)->dateTimeBetween($batch->start_date, 'now'),
                    'admin_notified_missed' => $faker->boolean(20),
                ]);
            }

            // --- Performance metrics (3 unique dates) using firstOrCreate ---
            $metricDates = [];
            while (count($metricDates) < 3) {
                $date = $faker->dateTimeBetween($batch->start_date, 'now')->format('Y-m-d');
                if (!in_array($date, $metricDates)) {
                    $metricDates[] = $date;
                }
            }
            foreach ($metricDates as $date) {
                PerformanceMetric::firstOrCreate(
                    ['poultry_batch_id' => $batch->id, 'date' => $date],
                    [
                        'age_days' => $faker->numberBetween(1, 60),
                        'average_weight' => $faker->randomFloat(3, 0.2, 2.5),
                        'daily_feed' => $faker->randomFloat(3, 0.01, 0.2),
                        'cumulative_feed' => $faker->randomFloat(3, 1, 100),
                        'ifcr' => $faker->randomFloat(4, 1.5, 2.5),
                        'cfcr' => $faker->randomFloat(4, 1.8, 3.0),
                        'marginal_profit_percent' => $faker->randomFloat(2, -10, 30),
                        'adg' => $faker->optional(0.7)->randomFloat(4, 0.01, 0.1),
                    ]
                );
            }

            // --- Investor investments ---
            $investors = User::where('role', 'investor')->get();
            if ($investors->count() > 0) {
                for ($i = 0; $i < 2; $i++) {
                    $investor = $investors->random();
                    $amount = $faker->randomFloat(2, 10000, 200000);
                    InvestorInvestment::create([
                        'investor_id' => $investor->id,
                        'poultry_batch_id' => $batch->id,
                        'amount_invested' => $amount,
                        'investment_date' => $faker->dateTimeBetween($batch->start_date, 'now')->format('Y-m-d'),
                        'batch_total_cost_at_investment' => $faker->randomFloat(2, 50000, 500000),
                        'investment_percentage' => $amount / max(1, $batch->total_expenses) * 100,
                        'is_active' => $faker->boolean(80),
                    ]);
                }
            }
        }

        // 10. Observation categories
        $catNames = ['Health', 'Feeding', 'Environment', 'Behavior', 'Production'];
        $observationCategories = [];
        foreach ($catNames as $name) {
            $cat = ObservationCategory::create([
                'name' => $name,
                'description' => $faker->sentence,
                'is_active' => true,
                'created_by_id' => $users[0]->id,
            ]);
            $observationCategories[] = $cat;
        }

        // 11. Observation reports
        for ($i = 0; $i < 5; $i++) {
            $batchIds = collect($batches)->pluck('id')->all();
            $reviewedBy = $faker->boolean(50) ? $faker->randomElement($users) : null;
            $resolvedBy = $faker->boolean(30) ? $faker->randomElement($users) : null;

            $followUpDate = $faker->optional(0.3)->dateTimeBetween('now', '+1 month');
            $followUpDateFormatted = $followUpDate ? $followUpDate->format('Y-m-d') : null;

            ObservationReport::create([
                'title' => $faker->sentence(3),
                'category_id' => $observationCategories[array_rand($observationCategories)]->id,
                'other_category' => $faker->optional(0.3)->word,
                'description' => $faker->paragraph,
                'affected_batch_ids' => $faker->randomElements($batchIds, $faker->numberBetween(1, 3)),
                'evidence_photos' => null,
                'status' => $faker->randomElement(['pending', 'reviewed', 'action_taken', 'resolved', 'closed']),
                'priority' => $faker->randomElement(['low', 'medium', 'high', 'critical']),
                'reported_by_id' => $users[array_rand($users)]->id,
                'reported_at' => $faker->dateTimeBetween('-6 months', 'now'),
                'reviewed_by_id' => $reviewedBy ? $reviewedBy->id : null,
                'reviewed_at' => $reviewedBy ? $faker->dateTimeBetween('-3 months', 'now') : null,
                'resolved_by_id' => $resolvedBy ? $resolvedBy->id : null,
                'resolved_at' => $resolvedBy ? $faker->dateTimeBetween('-2 months', 'now') : null,
                'admin_response' => $faker->optional(0.5)->paragraph,
                'actions_taken' => $faker->optional(0.5)->paragraph,
                'is_archived' => $faker->boolean(20),
                'requires_follow_up' => $faker->boolean(30),
                'follow_up_date' => $followUpDateFormatted,
            ]);
        }

        // 12. History queries
        for ($i = 0; $i < 5; $i++) {
            $userFilter = $faker->optional(0.5)->randomElement($users);
            $batchFilter = $faker->optional(0.5)->randomElement($batches);

            HistoryQuery::create([
                'name' => $faker->word . ' Query',
                'query_type' => $faker->randomElement(['batch', 'expense', 'feed', 'weight', 'general']),
                'date_from' => $faker->optional(0.7)->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                'date_to' => $faker->optional(0.7)->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
                'user_filter_id' => $userFilter ? $userFilter->id : null,
                'batch_filter_id' => $batchFilter ? $batchFilter->id : null,
                'category_filter' => $faker->optional(0.5)->word,
                'min_amount' => $faker->optional(0.5)->randomFloat(2, 100, 10000),
                'max_amount' => $faker->optional(0.5)->randomFloat(2, 10000, 100000),
                'result_count' => $faker->numberBetween(0, 100),
                'last_executed' => $faker->dateTimeBetween('-1 month', 'now'),
                'execution_time_ms' => $faker->numberBetween(10, 5000),
                'created_by_id' => $users[array_rand($users)]->id,
                'is_shared' => $faker->boolean(20),
            ]);
        }

        // 13. Notifications
        for ($i = 0; $i < 5; $i++) {
            Notification::create([
                'notification_type' => $faker->randomElement(['alert', 'reminder', 'info', 'warning']),
                'title' => $faker->sentence(3),
                'message' => $faker->paragraph,
                'batch_id' => $faker->optional(0.5)->randomElement($batches)->id,
                'observation_report_id' => $faker->optional(0.3)->randomElement(ObservationReport::all())->id,
                'is_active' => true,
                'created_by_id' => $users[array_rand($users)]->id,
            ]);
        }

        // 14. Notification read statuses
        $notifications = Notification::all();
        foreach ($notifications as $notification) {
            $readers = $faker->randomElements($users, $faker->numberBetween(1, 3));
            foreach ($readers as $user) {
                NotificationReadStatus::create([
                    'user_id' => $user->id,
                    'notification_id' => $notification->id,
                    'is_read' => $faker->boolean(70),
                    'read_at' => $faker->optional(0.7)->dateTimeBetween('-1 month', 'now'),
                ]);
            }
        }

        // 15. Export histories
        for ($i = 0; $i < 5; $i++) {
            ExportHistory::create([
                'user_id' => $users[array_rand($users)]->id,
                'export_format' => $faker->randomElement(['csv', 'excel', 'pdf']),
                'batch_id' => $faker->optional(0.5)->randomElement($batches)->id,
                'export_type' => $faker->randomElement(['batch', 'expense', 'feed', 'weight', 'full']),
                'file_name' => $faker->word . '.' . $faker->fileExtension,
                'file_size' => $faker->numberBetween(100, 10000),
                'exported_at' => $faker->dateTimeBetween('-6 months', 'now'),
                'expires_at' => $faker->optional(0.5)->dateTimeBetween('now', '+1 month'),
            ]);
        }

        $this->command->info('Full database seeding completed successfully!');
    }
}