<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\WeightRecordRequest;
use App\Models\Poultry\Batch;
use App\Models\Poultry\WeightRecord;
use App\Services\Poultry\BatchRecalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class WeightRecordController extends Controller
{
    /**
     * CV threshold for high variation warning (in percent).
     * Change this value to adjust the warning threshold.
     */
    const CV_THRESHOLD = 20;

    /**
     * Display weight records for a batch.
     */
    public function index(Batch $batch)
    {
        Gate::authorize('view', $batch);
        $records = $batch->weightRecords()->with('recordedBy')->latest('date')->paginate(20);
        return view('sectors.poultry.weight-records.index', compact('batch', 'records'));
    }

    /**
     * Show the create form (used by Form Hub AND resource route).
     */
    public function create(?Batch $batch = null)
    {
        Gate::authorize('create', WeightRecord::class);

        $batches = Batch::query()
            ->where('status', 'active')
            ->orderBy('start_date', 'desc')
            ->get();

        $requiredSample = $batch ? $batch->calculateRequiredSampleSize() : 0;

        return view('sectors.poultry.forms.weight-record', compact('batch', 'batches', 'requiredSample'));
    }

    /**
     * Store a new weight record.
     * - Calculates CV, mean, avg weight
     * - Saves record (even if CV is high)
     * - Recalculates batch metrics
     */
    public function store(WeightRecordRequest $request)
    {
        Gate::authorize('create', WeightRecord::class);

        $data = $request->validated();

        $batch = Batch::findOrFail($data['poultry_batch_id']);
        if ($batch->status !== 'active') {
            return redirect()->back()->with('error', 'Cannot add records to a closed or completed batch.');
        }

        $data['recorded_by_id'] = auth()->id();

        $cv = 0;

        DB::transaction(function () use ($data, $batch, &$cv) {
            $record = new WeightRecord($data);
            $record->calculateMetrics(); // sets cv_status, coefficient_variation, average_weight
            $record->save();

            $cv = (float) $record->coefficient_variation;

            BatchRecalculationService::recalculateAll($batch);
        });

        if ($cv >= self::CV_THRESHOLD) {
            session()->flash('warning', 'Weight record saved with high variation (CV ≥ ' . self::CV_THRESHOLD . '%). Please monitor the flock closely.');
        }

        return redirect()->route('poultry.batches.show', $batch)
            ->with('success', 'Weight record saved and metrics recalculated.');
    }

    /**
     * Show the edit form.
     */
    public function edit(WeightRecord $weightRecord)
    {
        Gate::authorize('update', $weightRecord);
        return view('sectors.poultry.weight-records.edit', compact('weightRecord'));
    }

    /**
     * Update a weight record.
     * Recalculates CV and batch metrics.
     */
    public function update(WeightRecordRequest $request, WeightRecord $weightRecord)
    {
        Gate::authorize('update', $weightRecord);

        $cv = 0;

        DB::transaction(function () use ($request, $weightRecord, &$cv) {
            $batch = $weightRecord->batch;

            $weightRecord->fill($request->validated());
            $weightRecord->calculateMetrics();
            $weightRecord->save();

            $cv = (float) $weightRecord->coefficient_variation;

            BatchRecalculationService::recalculateAll($batch);
        });

        if ($cv >= self::CV_THRESHOLD) {
            session()->flash('warning', 'Weight record updated with high variation (CV ≥ ' . self::CV_THRESHOLD . '%). Please monitor the flock closely.');
        }

        return redirect()->route('poultry.batches.show', $weightRecord->batch)
            ->with('success', 'Weight record updated and metrics recalculated.');
    }

    /**
     * Delete a weight record.
     * Full recalc happens automatically.
     */
    public function destroy(WeightRecord $weightRecord)
    {
        Gate::authorize('delete', $weightRecord);

        DB::transaction(function () use ($weightRecord) {
            $batch = $weightRecord->batch;
            $weightRecord->delete();
            BatchRecalculationService::recalculateAll($batch);
        });

        return redirect()->back()->with('success', 'Weight record deleted and metrics recalculated.');
    }
}