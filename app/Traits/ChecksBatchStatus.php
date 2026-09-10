<?php

namespace App\Traits;

use App\Models\Poultry\Batch;
use Illuminate\Http\Request;

trait ChecksBatchStatus
{
    protected function ensureBatchIsActive($batchId)
    {
        $batch = Batch::find($batchId);
        if (!$batch) {
            abort(404, 'Batch not found');
        }
        if ($batch->status !== 'active') {
            abort(403, 'This batch is no longer active. Records cannot be added.');
        }
        return $batch;
    }
}