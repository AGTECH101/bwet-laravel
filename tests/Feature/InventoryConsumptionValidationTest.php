<?php

namespace Tests\Feature;

use App\Models\Poultry\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryConsumptionValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_consumption_cannot_exceed_available_stock(): void
    {
        $user = User::factory()->create([
            'role' => 'manager',
            'is_approved' => true,
        ]);

        $this->actingAs($user);

        $item = InventoryItem::create([
            'name' => 'Starter Feed',
            'category' => 'feed',
            'unit' => 'kg',
            'quantity_in_stock' => 10,
            'quantity_used' => 2,
            'minimum_quantity' => 2,
            'vendor' => 'Farm Supply',
            'cost_per_unit' => 150,
            'is_active' => true,
            'status' => 'active',
            'created_by_id' => $user->id,
        ]);

        $response = $this->from(route('poultry.inventory-consumptions.create'))->post(route('poultry.inventory-consumptions.store'), [
            'inventory_item_id' => $item->id,
            'poultry_batch_id' => null,
            'quantity_used' => 15,
            'date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('quantity_used');
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'quantity_in_stock' => '10.000']);
    }

    public function test_batch_investment_includes_expenses_and_batch_usage_but_excludes_waste(): void
    {
        $user = User::factory()->create(['role' => 'manager', 'is_approved' => true]);
        $this->actingAs($user);

        $item = InventoryItem::create([
            'name' => 'Grower Feed',
            'category' => 'feed',
            'unit' => 'kg',
            'quantity_in_stock' => 100,
            'quantity_used' => 0,
            'minimum_quantity' => 10,
            'vendor' => 'Farm Supply',
            'cost_per_unit' => 200,
            'is_active' => true,
            'status' => 'active',
            'created_by_id' => $user->id,
        ]);

        $sector = \App\Models\Sector::firstOrCreate([
            'slug' => 'poultry-test',
        ], [
            'name' => 'Poultry Test',
            'description' => 'Test sector',
            'status' => 'active',
            'is_live' => true,
        ]);

        $batch = \App\Models\Poultry\Batch::create([
            'batch_id' => 'BATCHTEST01',
            'name' => 'Test Batch',
            'phase' => 'batch',
            'start_date' => now()->subDays(10),
            'starting_flock' => 500,
            'remaining_flock' => 500,
            'pen_id' => null,
            'initial_chicken_cost' => 5000,
            'selling_price_per_kg' => 700,
            'selling_price_per_carton' => 8500,
            'status' => 'active',
            'current_age_days' => 10,
            'total_mortality' => 0,
            'total_culls' => 0,
            'total_slaughter' => 0,
            'total_feed_used' => 0,
            'bags_consumed' => 0,
            'total_weight_gain' => 0,
            'current_ifcr' => 0,
            'current_cfcr' => 0,
            'current_marginal_profit_percent' => 0,
            'total_expenses' => 0,
            'cost_allocated_so_far' => 0,
            'peak_profit' => 0,
            'profit_margin_used' => 0,
            'stop_loss_used_percent' => 0,
            'is_manual_mode' => false,
            'created_by_id' => $user->id,
            'sector_id' => $sector->id,
        ]);

        \App\Models\Poultry\Expense::create([
            'poultry_batch_id' => $batch->id,
            'date' => now()->toDateString(),
            'category' => 'medicine',
            'description' => 'Medication',
            'amount' => 1500,
            'receipt_number' => 'REC-100',
            'vendor' => 'Vet Co.',
            'recorded_by_id' => $user->id,
        ]);

        \App\Models\Poultry\InventoryConsumption::create([
            'inventory_item_id' => $item->id,
            'poultry_batch_id' => $batch->id,
            'quantity_used' => 10,
            'date' => now()->toDateString(),
            'unit_cost_at_time' => 200,
            'total_cost' => 2000,
            'source_type' => 'manual',
            'recorded_by_id' => $user->id,
            'reason' => 'Batch feed usage',
            'notes' => 'Used in the batch for the day.',
        ]);

        \App\Models\Poultry\InventoryConsumption::create([
            'inventory_item_id' => $item->id,
            'poultry_batch_id' => $batch->id,
            'quantity_used' => 5,
            'date' => now()->toDateString(),
            'unit_cost_at_time' => 200,
            'total_cost' => 1000,
            'source_type' => 'waste',
            'recorded_by_id' => $user->id,
            'reason' => 'Spoilage',
            'notes' => 'Bag torn and stock spoiled before use.',
        ]);

        $this->assertSame(8500.0, round($batch->calculateTotalInvestment(), 2));
    }
}
