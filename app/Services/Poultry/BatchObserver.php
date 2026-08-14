<?php

namespace App\Observers\Poultry;

use App\Models\Poultry\Batch;
use App\Models\Poultry\WeighingSchedule;

class BatchObserver
{
    public function created(Batch $batch)
    {
        WeighingSchedule::generateForBatch($batch);
    }

    public function updated(Batch $batch)
    {
        // Optionally update metrics if needed
    }
}