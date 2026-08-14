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
        WeighingSchedule::generateForBatch($batch);
    }

    /**
     * Handle the Batch "updated" event.
     */
    public function updated(Batch $batch): void
    {
        // If batch status changes from active to closed/completed, release the pen
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

        // Auto-update metrics when relevant fields change
        $dirtyFields = $batch->getDirty();
        $relevantFields = ['starting_flock', 'remaining_flock', 'status', 'phase', 'pen_id'];
        if (array_intersect(array_keys($dirtyFields), $relevantFields)) {
            $batch->updateCachedMetrics();
        }
    }

    /**
     * Handle the Batch "deleted" event.
     */
    public function deleted(Batch $batch): void
    {
        // Release the pen if occupied
        $this->releasePen($batch);
    }

    /**
     * Release the pen assigned to this batch.
     */
    private function releasePen(Batch $batch): void
    {
        if ($batch->pen) {
            $batch->pen->vacate();
            // Nullify pen_id on batch to keep consistency
            $batch->pen_id = null;
            $batch->saveQuietly(); // Avoid recursion
        }
    }
}