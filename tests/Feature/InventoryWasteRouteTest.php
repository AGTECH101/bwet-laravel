<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InventoryWasteRouteTest extends TestCase
{
    public function test_inventory_waste_route_and_schema_are_available(): void
    {
        $this->assertTrue(Route::has('poultry.forms.inventory-waste.create'));
        $this->assertTrue(Schema::hasColumn('inventory_consumptions', 'reason'));
        $this->assertTrue(Schema::hasColumn('inventory_consumptions', 'notes'));
    }
}
