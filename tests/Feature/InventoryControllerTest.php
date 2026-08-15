<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InventoryControllerTest extends TestCase
{
    public function test_inventory_controller_classes_are_available()
    {
        $this->assertTrue(class_exists(\App\Http\Controllers\Poultry\InventoryController::class));
        $this->assertTrue(class_exists(\App\Http\Controllers\Poultry\InventoryConsumptionController::class));
        $this->assertTrue(Route::has('poultry.inventory.index'));
        $this->assertTrue(Route::has('poultry.inventory-consumptions.create'));
    }
}
