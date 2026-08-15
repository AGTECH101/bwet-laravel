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
}
