<?php

namespace Tests\Unit;

use App\Models\Poultry\Batch;
use App\Services\Poultry\BatchCalculationService;
use Tests\TestCase;

class BatchCalculationServiceTest extends TestCase
{
    public function test_required_sample_size_handles_missing_flock_value(): void
    {
        $this->assertSame(0, BatchCalculationService::calculateRequiredSampleSize(null));
        $this->assertSame(5, BatchCalculationService::calculateRequiredSampleSize(25));
    }

    public function test_batch_model_can_calculate_total_investment_without_data(): void
    {
        $batch = new Batch();

        $this->assertSame(0.0, $batch->calculateTotalInvestment());
    }
}
