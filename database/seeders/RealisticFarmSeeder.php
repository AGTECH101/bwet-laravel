<?php

namespace Database\Seeders;

use App\Models\ExportHistory;
use App\Models\HistoryQuery;
use App\Models\MarketPrice;
use App\Models\Notification;
use App\Models\ObservationCategory;
use App\Models\ObservationReport;
use App\Models\Sector;
use App\Models\SystemVariable;
use App\Models\User;
use App\Models\Poultry\Batch;
use App\Models\Poultry\Expense;
use App\Models\Poultry\FeedRecord;
use App\Models\Poultry\FlockRecord;
use App\Models\Poultry\InventoryConsumption;
use App\Models\Poultry\InventoryItem;
use App\Models\Poultry\Pen;
use App\Models\Poultry\WeightRecord;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RealisticFarmSeeder extends Seeder
{
    public function run(): void
    {
        $sector = Sector::firstOrCreate(
            ['slug' => 'poultry'],
            ['name' => 'Poultry', 'description' => 'Broiler production and farm operations', 'status' => 'active', 'is_live' => true]
        );

        foreach (['admin', 'manager', 'staff', 'investor'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        $users = [
            ['name' => 'Ada Admin', 'email' => 'ada.admin@bwetfarms.com', 'password' => bcrypt('password'), 'role' => 'admin', 'phone' => '08030000001', 'farm_location' => 'Lagos', 'is_approved' => true],
            ['name' => 'Kofi Manager', 'email' => 'kofi.manager@bwetfarms.com', 'password' => bcrypt('password'), 'role' => 'manager', 'phone' => '08030000002', 'farm_location' => 'Ibadan', 'is_approved' => true],
            ['name' => 'Tina Staff', 'email' => 'tina.staff@bwetfarms.com', 'password' => bcrypt('password'), 'role' => 'staff', 'phone' => '08030000003', 'farm_location' => 'Abeokuta', 'is_approved' => true],
            ['name' => 'Emeka Staff', 'email' => 'emeka.staff@bwetfarms.com', 'password' => bcrypt('password'), 'role' => 'staff', 'phone' => '08030000004', 'farm_location' => 'Ondo', 'is_approved' => true],
            ['name' => 'Mariam Investor', 'email' => 'mariam.investor@bwetfarms.com', 'password' => bcrypt('password'), 'role' => 'investor', 'phone' => '08030000005', 'farm_location' => 'Abuja', 'is_approved' => true],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(['email' => $userData['email']], $userData);
            $user->assignRole($userData['role']);
        }

        $userMap = User::all()->keyBy('role');

        $pens = [
            ['name' => 'Pen A1', 'pen_code' => 'A1', 'pen_type' => 'brooding', 'capacity' => 1500, 'is_active' => true],
            ['name' => 'Pen B2', 'pen_code' => 'B2', 'pen_type' => 'batch', 'capacity' => 2000, 'is_active' => true],
            ['name' => 'Pen C3', 'pen_code' => 'C3', 'pen_type' => 'batch', 'capacity' => 1800, 'is_active' => true],
            ['name' => 'Pen D4', 'pen_code' => 'D4', 'pen_type' => 'batch', 'capacity' => 2200, 'is_active' => true],
        ];

        foreach ($pens as $penData) {
            Pen::firstOrCreate(['pen_code' => $penData['pen_code']], array_merge($penData, ['created_by_id' => $userMap['admin']->id]));
        }

        $defaultVars = [
            ['name' => 'Profit Margin', 'key' => 'profit_margin', 'category' => 'financial', 'value' => '20', 'data_type' => 'percentage'],
            ['name' => 'Dress Percentage', 'key' => 'dress_percentage', 'category' => 'financial', 'value' => '75', 'data_type' => 'percentage'],
            ['name' => 'Daily Profit Tolerance (%)', 'key' => 'daily_profit_tolerance', 'category' => 'performance', 'value' => '-15', 'data_type' => 'percentage'],
            ['name' => 'FCR Efficiency Tolerance (%)', 'key' => 'fcr_efficiency_tolerance', 'category' => 'performance', 'value' => '20', 'data_type' => 'percentage'],
            ['name' => 'Stop Loss Amount (₦)', 'key' => 'stop_loss_amount', 'category' => 'financial', 'value' => '20000', 'data_type' => 'decimal'],
        ];

        foreach ($defaultVars as $var) {
            SystemVariable::firstOrCreate(['key' => $var['key']], $var);
        }

        $categories = ['Health & welfare', 'Feed & nutrition', 'Water management', 'Biosecurity', 'Other'];
        foreach ($categories as $categoryName) {
            ObservationCategory::firstOrCreate(['name' => $categoryName], ['description' => 'Operational observation', 'is_active' => true, 'created_by_id' => $userMap['admin']->id]);
        }

        MarketPrice::firstOrCreate(
            ['effective_date' => Carbon::today()->toDateString()],
            ['price_per_bird' => 1900.00, 'price_per_kg' => 650.00, 'price_per_carton' => 8500.00, 'effective_date' => Carbon::today()->toDateString(), 'notes' => 'Current farm market reference', 'is_active' => true, 'set_by_id' => $userMap['admin']->id]
        );

        $inventoryDefinitions = [
            ['name' => 'Broiler Starter Feed', 'category' => 'feed', 'unit' => 'kg', 'quantity_in_stock' => 860, 'minimum_quantity' => 200, 'cost_per_unit' => 915, 'vendor' => 'Farmline Feeds'],
            ['name' => 'Grower Feed', 'category' => 'feed', 'unit' => 'kg', 'quantity_in_stock' => 720, 'minimum_quantity' => 180, 'cost_per_unit' => 890, 'vendor' => 'Farmline Feeds'],
            ['name' => 'Finisher Feed', 'category' => 'feed', 'unit' => 'kg', 'quantity_in_stock' => 640, 'minimum_quantity' => 170, 'cost_per_unit' => 860, 'vendor' => 'Farmline Feeds'],
            ['name' => 'Vaccine - ND', 'category' => 'vaccine', 'unit' => 'ml', 'quantity_in_stock' => 92, 'minimum_quantity' => 20, 'cost_per_unit' => 2300, 'vendor' => 'AgriVet Supply'],
            ['name' => 'Vitamin Pack', 'category' => 'medicine', 'unit' => 'box', 'quantity_in_stock' => 36, 'minimum_quantity' => 8, 'cost_per_unit' => 3200, 'vendor' => 'AgriVet Supply'],
        ];

        $inventoryItems = [];
        foreach ($inventoryDefinitions as $itemData) {
            $item = InventoryItem::firstOrCreate(['name' => $itemData['name']], array_merge($itemData, ['quantity_used' => 0, 'is_active' => true, 'status' => 'active', 'created_by_id' => $userMap['admin']->id]));
            $inventoryItems[] = $item;
        }

        $penIds = Pen::pluck('id')->all();
        $batchNames = [
            ['batch_id' => 'BATCHFQJ', 'name' => 'Batch FQJ', 'phase' => 'batch', 'starting_flock' => 1800, 'start_date' => Carbon::today()->subDays(34), 'pen_id' => $penIds[0] ?? null, 'initial_chicken_cost' => 1800 * 1100, 'mortality' => 110, 'culls' => 30, 'slaughter' => 0, 'feed_used' => 4100, 'expenses' => 210000, 'cost_allocated_so_far' => 165000],
            ['batch_id' => 'BATCHA12', 'name' => 'Batch A12', 'phase' => 'batch', 'starting_flock' => 2200, 'start_date' => Carbon::today()->subDays(48), 'pen_id' => $penIds[1] ?? null, 'initial_chicken_cost' => 2200 * 1050, 'mortality' => 140, 'culls' => 42, 'slaughter' => 0, 'feed_used' => 5900, 'expenses' => 245000, 'cost_allocated_so_far' => 220000],
            ['batch_id' => 'BATCHT77', 'name' => 'Batch T77', 'phase' => 'brooding', 'starting_flock' => 1400, 'start_date' => Carbon::today()->subDays(18), 'pen_id' => $penIds[2] ?? null, 'initial_chicken_cost' => 1400 * 980, 'mortality' => 55, 'culls' => 22, 'slaughter' => 0, 'feed_used' => 1850, 'expenses' => 98000, 'cost_allocated_so_far' => 82000],
        ];

        $batches = [];
        foreach ($batchNames as $batchData) {
            $remainingFlock = max(0, (int) $batchData['starting_flock'] - $batchData['mortality'] - $batchData['culls'] - $batchData['slaughter']);

            $batch = Batch::firstOrCreate(['batch_id' => $batchData['batch_id']], [
                'name' => $batchData['name'],
                'phase' => $batchData['phase'],
                'starting_flock' => $batchData['starting_flock'],
                'start_date' => $batchData['start_date'],
                'pen_id' => $batchData['pen_id'],
                'initial_chicken_cost' => $batchData['initial_chicken_cost'],
                'remaining_flock' => $remainingFlock,
                'hatchery' => 'Anike Breeders',
                'selling_price_per_kg' => 760.00,
                'selling_price_per_carton' => 9200.00,
                'status' => 'active',
                'current_age_days' => Carbon::today()->diffInDays(Carbon::parse($batchData['start_date'])),
                'total_mortality' => (int) $batchData['mortality'],
                'total_culls' => (int) $batchData['culls'],
                'total_slaughter' => (int) $batchData['slaughter'],
                'total_feed_used' => (float) $batchData['feed_used'],
                'bags_consumed' => round($batchData['feed_used'] / 25, 2),
                'total_weight_gain' => 1800.0,
                'current_ifcr' => 1.78,
                'current_cfcr' => 2.05,
                'current_marginal_profit_percent' => 16.3,
                'total_expenses' => (float) $batchData['expenses'],
                'cost_allocated_so_far' => (float) $batchData['cost_allocated_so_far'],
                'peak_profit' => 180000.0,
                'profit_margin_used' => 18.5,
                'stop_loss_used_percent' => 6.25,
                'is_manual_mode' => false,
                'created_by_id' => $userMap['admin']->id,
                'sector_id' => $sector->id,
            ]);
            $batches[] = $batch;
        }

        foreach ($batches as $index => $batch) {
            FlockRecord::firstOrCreate(
                ['poultry_batch_id' => $batch->id, 'date' => Carbon::parse($batch->start_date)->addDays(7)->toDateString()],
                ['mortality' => 12, 'culls' => 3, 'slaughter' => 0, 'notes' => 'Routine flock check', 'recorded_by_id' => $userMap['staff']->id]
            );

            FlockRecord::firstOrCreate(
                ['poultry_batch_id' => $batch->id, 'date' => Carbon::parse($batch->start_date)->addDays(21)->toDateString()],
                ['mortality' => 9, 'culls' => 4, 'slaughter' => 0, 'notes' => 'Feed intake stabilized', 'recorded_by_id' => $userMap['manager']->id]
            );

            $weights = [0.42, 0.46, 0.51, 0.49, 0.54, 0.52, 0.48, 0.55, 0.57, 0.53];
            WeightRecord::firstOrCreate(
                ['poultry_batch_id' => $batch->id, 'date' => Carbon::today()->subDays(7)->toDateString()],
                ['individual_weights' => json_encode($weights), 'birds_weighed' => count($weights), 'total_weight' => array_sum($weights), 'average_weight' => array_sum($weights)/count($weights), 'coefficient_variation' => 8.3, 'cv_status' => 'excellent', 'records_count' => count($weights), 'recorded_by_id' => $userMap['staff']->id]
            );

            FeedRecord::firstOrCreate(
                ['poultry_batch_id' => $batch->id, 'date' => Carbon::today()->subDays(3)->toDateString(), 'inventory_item_id' => $inventoryItems[0]->id],
                ['feed_used' => 120.5, 'feed_cost_per_kg' => 915.00, 'total_feed_cost' => 110236.00, 'feed_per_bird' => 0.07, 'recorded_by_id' => $userMap['staff']->id]
            );

            Expense::firstOrCreate(
                ['poultry_batch_id' => $batch->id, 'description' => 'Medication - Coccidiosis prevention', 'date' => Carbon::today()->subDays(5)->toDateString()],
                ['category' => 'medication', 'amount' => 25000.00, 'receipt_number' => 'RX-' . ($index + 1), 'vendor' => 'AgriVet Supply', 'recorded_by_id' => $userMap['manager']->id]
            );
        }

        foreach ($batches as $batch) {
            InventoryConsumption::firstOrCreate(
                ['inventory_item_id' => $inventoryItems[0]->id, 'poultry_batch_id' => $batch->id, 'date' => Carbon::today()->subDays(2)->toDateString()],
                ['quantity_used' => 42.5, 'unit_cost_at_time' => 915.00, 'total_cost' => 38937.50, 'source_type' => 'manual', 'recorded_by_id' => $userMap['staff']->id, 'reason' => 'Batch feed usage', 'notes' => 'Feed transferred to the batch and reconciled against the stock ledger.']
            );

            InventoryConsumption::firstOrCreate(
                ['inventory_item_id' => $inventoryItems[3]->id, 'poultry_batch_id' => $batch->id, 'date' => Carbon::today()->subDays(6)->toDateString()],
                ['quantity_used' => 1.2, 'unit_cost_at_time' => 2300.00, 'total_cost' => 2760.00, 'source_type' => 'manual', 'recorded_by_id' => $userMap['manager']->id, 'reason' => 'Vaccination drive', 'notes' => 'Vaccination was recorded for the flock after route check and stock verification.']
            );
        }

        $observationTargetBatch = $batches[0]->id;
        ObservationReport::firstOrCreate(
            ['title' => 'Water line pressure drop observed'],
            ['category_id' => ObservationCategory::where('name', 'Water management')->first()->id, 'description' => 'Water pressure dropped in one pen and birds appeared restless for around 20 minutes before the issue was resolved.', 'priority' => 'high', 'reported_by_id' => $userMap['staff']->id, 'reported_at' => Carbon::now()->subHours(9), 'status' => 'pending', 'affected_batch_ids' => [$observationTargetBatch]]
        );

        Notification::firstOrCreate(
            ['title' => 'New batch performance update'],
            ['notification_type' => 'batch_update', 'message' => 'Batch FQJ is trending above target feed conversion after the latest review.', 'created_by_id' => $userMap['manager']->id, 'batch_id' => $batches[0]->id, 'is_active' => true]
        );

        ExportHistory::firstOrCreate(
            ['file_name' => 'batch-fqj-export.csv'],
            ['user_id' => $userMap['admin']->id, 'export_format' => 'csv', 'batch_id' => $batches[0]->id, 'export_type' => 'batch', 'file_size' => 42500, 'exported_at' => Carbon::now()->subDay(), 'expires_at' => Carbon::now()->addDays(14)]
        );

        HistoryQuery::firstOrCreate(
            ['name' => 'Feed and mortality summary'],
            ['query_type' => 'custom', 'date_from' => Carbon::now()->subDays(30), 'date_to' => Carbon::now(), 'batch_filter_id' => $batches[0]->id, 'created_by_id' => $userMap['manager']->id, 'result_count' => 12, 'last_executed' => Carbon::now()->subHours(3), 'execution_time_ms' => 320]
        );

        $this->command->info('Realistic farm seed data has been loaded.');
    }
}
