<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ObservationAndTransferRouteTest extends TestCase
{
    public function test_review_route_and_view_exist(): void
    {
        $this->assertTrue(Route::has('observations.review'));
        $this->assertTrue(Route::has('observations.review.form'));
        $this->assertTrue(view()->exists('general.observations.review'));
    }

    public function test_batch_transfer_routes_exist(): void
    {
        $this->assertTrue(Route::has('poultry.forms.batch-transfer'));
        $this->assertTrue(Route::has('poultry.forms.batch-transfer.store'));
        $this->assertTrue(view()->exists('sectors.poultry.forms.batch-transfer'));
    }
}
