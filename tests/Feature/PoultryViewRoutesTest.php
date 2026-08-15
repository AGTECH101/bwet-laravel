<?php

namespace Tests\Feature;

use Tests\TestCase;

class PoultryViewRoutesTest extends TestCase
{
    public function test_required_poultry_views_exist_for_resource_and_form_routes(): void
    {
        $this->assertTrue(view()->exists('sectors.poultry.flock-records.create'));
        $this->assertTrue(view()->exists('sectors.poultry.flock-records.edit'));
        $this->assertTrue(view()->exists('sectors.poultry.weight-records.create'));
        $this->assertTrue(view()->exists('sectors.poultry.weight-records.edit'));
        $this->assertTrue(view()->exists('sectors.poultry.feed-records.create'));
        $this->assertTrue(view()->exists('sectors.poultry.feed-records.edit'));
        $this->assertTrue(view()->exists('sectors.poultry.expenses.create'));
        $this->assertTrue(view()->exists('sectors.poultry.expenses.edit'));
        $this->assertTrue(view()->exists('sectors.poultry.inventory-consumptions.index'));
        $this->assertTrue(view()->exists('sectors.poultry.analytics.global'));
        $this->assertNotNull(route('poultry.forms.price-calculator.create'));
        $this->assertTrue(view()->exists('sectors.poultry.forms.price-calculator'));
    }
}
