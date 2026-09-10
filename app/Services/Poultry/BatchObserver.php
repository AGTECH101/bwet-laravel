<?php

namespace App\Observers\Poultry;

use App\Models\Poultry\Batch;
use App\Models\Poultry\Pen;
use App\Models\Poultry\WeighingSchedule;

class BatchObserver
{
    /**
     * Handle the Batch "created" event.
     */
    public function created(Batch $batch): void
    {
        // Generate weighing schedule for the new batch
        if ($batch->phase === 'brooding' || $batch->phase === 'batch') {
            WeighingSchedule::generateForBatch($batch);
        }
    }

    /**
     * Handle the Batch "updated" event.
     * NOTE: We do NOT recalculate state here anymore – the recalc service
     * is called explicitly after any state-changing operation.
     */
    public function updated(Batch $batch): void
    {
        // If batch status changes to closed/completed, release the pen
        if ($batch->wasChanged('status') && $batch->status !== 'active') {
            $originalStatus = $batch->getOriginal('status');
            if ($originalStatus === 'active') {
                $this->releasePen($batch);
            }
        }

        // If pen was changed manually, ensure old pen is released
        if ($batch->wasChanged('pen_id')) {
            $oldPenId = $batch->getOriginal('pen_id');
            if ($oldPenId) {
                $oldPen = Pen::find($oldPenId);
                if ($oldPen && $oldPen->current_batch_id === $batch->id) {
                    $oldPen->vacate();
                }
            }
        }
    }

    /**
     * Handle the Batch "deleted" event.
     */
    public function deleted(Batch $batch): void
    {
        $this->releasePen($batch);
    }

    /**
     * Release the pen assigned to this batch.
     */
    private function releasePen(Batch $batch): void
    {
        if ($batch->pen) {
            $batch->pen->vacate();
        }
    }
}