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

    public function create(Batch $batch)
    {
        Gate::authorize('create', WeightRecord::class);
        $requiredSample = $batch->calculateRequiredSampleSize();
        return view('sectors.poultry.weight-records.create', compact('batch', 'requiredSample'));
    }

    public function store(WeightRecordRequest $request)
    {
        Gate::authorize('create', WeightRecord::class);

        $data = $request->validated();
        $data['recorded_by_id'] = auth()->id();

        $record = WeightRecord::create($data);
        $record->calculateMetrics(); // defined in model?
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

        $weightRecord->update($request->validated());
        $weightRecord->calculateMetrics();
        $weightRecord->save();
        $weightRecord->batch->updateCachedMetrics();

        return redirect()->route('poultry.batches.show', $weightRecord->batch)
            ->with('success', 'Weight record updated.');
    }

        public function calculateMetrics(): void
    {
        $weights = $this->individual_weights ?? [];
        if (empty($weights)) {
            return;
        }

        $num = count($weights);
        $this->birds_weighed = $num;
        $this->total_weight = array_sum($weights);
        $this->average_weight = $this->total_weight / $num;

        // CV
        $mean = $this->average_weight;
        $variance = array_sum(array_map(fn($w) => ($w - $mean) ** 2, $weights)) / $num;
        $stddev = sqrt($variance);
        $cv = $mean > 0 ? ($stddev / $mean) * 100 : 0;
        $this->coefficient_variation = round($cv, 2);

        // CV status
        if ($cv >= 15) {
            $this->cv_status = 'rejected';
            $this->is_valid_sample = false;
        } elseif ($cv >= 12) {
            $this->cv_status = 'warning';
            $this->is_valid_sample = true;
        } elseif ($cv >= 10) {
            $this->cv_status = 'caution';
            $this->is_valid_sample = true;
        } else {
            $this->cv_status = 'excellent';
            $this->is_valid_sample = true;
        }

        // Expected weight (just a placeholder)
        $this->expected_weight = $this->calculateExpectedWeight();
    }

    protected function calculateExpectedWeight(): float
    {
        // Simple growth curve placeholder
        $age = $this->batch?->current_age_days ?? 0;
        if ($age <= 0) return 0.045;
        if ($age <= 7) return 0.18;
        if ($age <= 14) return 0.45;
        if ($age <= 21) return 0.85;
        if ($age <= 28) return 1.30;
        if ($age <= 35) return 1.80;
        if ($age <= 42) return 2.20;
        if ($age <= 49) return 2.50;
        return 2.70;
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