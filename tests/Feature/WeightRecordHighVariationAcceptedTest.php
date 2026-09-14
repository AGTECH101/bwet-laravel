<?php

namespace Tests\Feature;

use App\Models\Poultry\Batch;
use App\Models\Poultry\WeightRecord;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeightRecordHighVariationAcceptedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A sample with CV >= 15% must be accepted, saved with
     * cv_status = 'high', flagged is_valid_sample = true, and
     * surfaced to the user via a warning flash.
     */
    public function test_high_variation_weight_record_is_accepted_and_tagged_high(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'is_approved' => true,
        ]);

        $sector = Sector::create([
            'name' => 'Poultry',
            'slug' => 'poultry',
            'description' => 'Test poultry sector',
        ]);

        $batch = Batch::create([
            'batch_id' => 'B-TEST-001',
            'name' => 'Test Batch',
            'start_date' => now()->subDays(20),
            'starting_flock' => 100,
            'remaining_flock' => 100,
            'status' => 'active',
            'phase' => 'batch',
            'sector_id' => $sector->id,
            'created_by_id' => $user->id,
        ]);

        // Wide spread of weights → CV well above 15%.
        $response = $this->actingAs($user)
            ->post(route('poultry.forms.weight-record.store'), [
                'poultry_batch_id' => $batch->id,
                'date' => now()->toDateString(),
                'individual_weights' => [
                    0.35, 0.40, 0.95, 2.65, 1.80,
                    0.50, 1.85, 0.45, 2.45, 0.30,
                ],
                'notes' => 'High variation sample',
            ]);

        // Must redirect on success (not bounce back with validation errors).
        $response->assertRedirect(route('poultry.batches.show', $batch));
        $response->assertSessionHas('success');
        $response->assertSessionHas('warning');
        $response->assertSessionHasNoErrors();

        // Record persisted with the right tags.
        $this->assertDatabaseCount('weight_records', 1);

        $record = WeightRecord::first();
        $this->assertSame('high', $record->cv_status);
        $this->assertTrue((bool) $record->is_valid_sample);
        $this->assertGreaterThanOrEqual(15.0, (float) $record->coefficient_variation);

        // The batch's average weight must reflect the new sample.
        $batch->refresh();
        $this->assertGreaterThan(0, (float) $batch->current_average_weight);
    }

    /**
     * A low-variation sample should save with 'excellent' and no warning.
     */
    public function test_low_variation_weight_record_saves_without_warning(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'is_approved' => true,
        ]);

        $sector = Sector::create([
            'name' => 'Poultry',
            'slug' => 'poultry',
            'description' => 'Test poultry sector',
        ]);

        $batch = Batch::create([
            'batch_id' => 'B-TEST-002',
            'name' => 'Tight Batch',
            'start_date' => now()->subDays(20),
            'starting_flock' => 100,
            'remaining_flock' => 100,
            'status' => 'active',
            'phase' => 'batch',
            'sector_id' => $sector->id,
            'created_by_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->post(route('poultry.forms.weight-record.store'), [
                'poultry_batch_id' => $batch->id,
                'date' => now()->toDateString(),
                'individual_weights' => [1.80, 1.82, 1.81, 1.79, 1.80, 1.83, 1.81, 1.80],
                'notes' => 'Uniform sample',
            ]);

        $response->assertRedirect(route('poultry.batches.show', $batch));
        $response->assertSessionHas('success');
        $response->assertSessionMissing('warning');

        $record = WeightRecord::first();
        $this->assertSame('excellent', $record->cv_status);
        $this->assertTrue((bool) $record->is_valid_sample);
    }
}