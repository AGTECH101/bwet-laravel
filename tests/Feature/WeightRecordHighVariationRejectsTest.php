<?php

namespace Tests\Feature;

use App\Models\Poultry\Batch;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeightRecordHighVariationRejectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_high_variation_weight_records_are_rejected(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $sector = Sector::create([
            'name' => 'Poultry',
            'slug' => 'poultry',
            'description' => 'Test poultry sector',
            'is_active' => true,
        ]);

        Batch::create([
            'batch_id' => 'B-TEST-001',
            'name' => 'Test Batch',
            'start_date' => now()->subDays(20),
            'starting_flock' => 100,
            'remaining_flock' => 100,
            'status' => 'active',
            'sector_id' => $sector->id,
        ]);

        $response = $this->actingAs($user)
            ->post(route('poultry.forms.weight-record.store'), [
                'poultry_batch_id' => 1,
                'date' => now()->toDateString(),
                'individual_weights' => [0.35, 0.40, 0.95, 2.65, 1.80, 0.50, 1.85, 0.45, 2.45, 0.30],
                'notes' => 'High variation sample',
            ]);

        $response->assertSessionHasErrors('individual_weights');
        $this->assertDatabaseCount('weight_records', 0);
    }
}
