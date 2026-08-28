<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\WeightRecordRequest;
use App\Models\Poultry\Batch;
use App\Models\Poultry\WeightRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WeightRecordController extends Controller
{
    public function index(Batch $batch)
    {
        Gate::authorize('view', $batch);
        $records = $batch->weightRecords()->with('recordedBy')->latest('date')->paginate(20);
        return view('sectors.poultry.weight-records.index', compact('batch', 'records'));
    }

    public function create(?Batch $batch = null)
    {
        Gate::authorize('create', WeightRecord::class);

        $batches = Batch::query()
            ->where('status', 'active')
            ->orderBy('start_date', 'desc')
            ->get();

        $requiredSample = $batch ? $batch->calculateRequiredSampleSize() : 0;

        return view('sectors.poultry.weight-records.create', compact('batch', 'batches', 'requiredSample'));
    }

    public function store(WeightRecordRequest $request)
    {
        Gate::authorize('create', WeightRecord::class);

        $data = $request->validated();

        // Check batch status
        $batch = Batch::findOrFail($data['poultry_batch_id']);
        if ($batch->status !== 'active') {
            return redirect()->back()->with('error', 'Cannot add records to a closed or completed batch.');
        }

        $weights = $data['individual_weights'] ?? [];

        if (is_array($weights) && count($weights) >= 2) {
            $numericWeights = array_values(array_filter(array_map(static fn ($item) => is_numeric($item) ? (float) $item : null, $weights), static fn ($item) => $item !== null));
            $mean = array_sum($numericWeights) / count($numericWeights);
            $variance = array_sum(array_map(static fn ($weight) => ($weight - $mean) ** 2, $numericWeights)) / count($numericWeights);
            $stdDev = sqrt($variance);
            $cv = $mean > 0 ? ($stdDev / $mean) * 100 : 0;

            if ($cv >= 15) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['individual_weights' => 'High weight variation detected (CV >= 15%). Please re-take the sample before saving.']);
            }
        }

        $data['recorded_by_id'] = auth()->id();

        $record = new WeightRecord($data);
        $record->calculateMetrics();

        if ($record->cv_status === 'rejected' || $record->is_valid_sample === false) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['individual_weights' => 'High weight variation detected (CV >= 15%). Please re-take the sample before saving.']);
        }

        $record->save();
        $record->batch->updateCachedMetrics();

        return redirect()->route('poultry.batches.show', $record->batch)
            ->with('success', 'Weight record saved.');
    }

    public function edit(WeightRecord $weightRecord)
    {
        Gate::authorize('update', $weightRecord);
        return view('sectors.poultry.weight-records.edit', compact('weightRecord'));
    }

    public function update(WeightRecordRequest $request, WeightRecord $weightRecord)
    {
        Gate::authorize('update', $weightRecord);

        $weightRecord->fill($request->validated());
        $weightRecord->calculateMetrics();

        if ($weightRecord->cv_status === 'rejected' || $weightRecord->is_valid_sample === false) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['individual_weights' => 'High weight variation detected (CV >= 15%). Please re-take the sample before saving.']);
        }

        $weightRecord->save();
        $weightRecord->batch->updateCachedMetrics();

        return redirect()->route('poultry.batches.show', $weightRecord->batch)
            ->with('success', 'Weight record updated.');
    }

    public function destroy(WeightRecord $weightRecord)
    {
        Gate::authorize('delete', $weightRecord);

        $batch = $weightRecord->batch;
        $weightRecord->delete();
        $batch->updateCachedMetrics();

        return redirect()->back()->with('success', 'Weight record deleted.');
    }
}