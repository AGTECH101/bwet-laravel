<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\FlockRecordRequest;
use App\Models\Poultry\Batch;
use App\Models\Poultry\FlockRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class FlockRecordController extends Controller
{
    public function index(Batch $batch)
    {
        Gate::authorize('view', $batch);
        $records = $batch->flockRecords()->with('recordedBy')->latest('date')->paginate(20);
        return view('sectors.poultry.flock-records.index', compact('batch', 'records'));
    }

    public function create(?Batch $batch = null)
    {
        Gate::authorize('create', FlockRecord::class);

        $batches = Batch::query()
            ->where('status', 'active')
            ->orderBy('start_date', 'desc')
            ->get();

        return view('sectors.poultry.flock-records.create', compact('batch', 'batches'));
    }

    public function store(FlockRecordRequest $request)
    {
        Gate::authorize('create', FlockRecord::class);

        $data = $request->validated();
        $data['recorded_by_id'] = auth()->id();

        $batch = Batch::findOrFail($data['poultry_batch_id']);
        if ($batch->status !== 'active') {
            return redirect()->back()->with('error', 'Cannot add records to a closed or completed batch.');
        }

        $record = DB::transaction(function () use ($data, $batch) {
            $record = FlockRecord::create($data);

            $reduction = ($record->mortality ?? 0) + ($record->culls ?? 0) + ($record->slaughter ?? 0);
            if ($reduction > 0) {
                $avgWeight = $batch->current_average_weight;
                $weightLost = $reduction * $avgWeight;

                // Update count and weight
                $batch->current_count -= $reduction;
                $batch->current_weight_kg -= $weightLost;
                $batch->current_average_weight = $batch->current_count > 0
                    ? $batch->current_weight_kg / $batch->current_count
                    : 0;

                // --- Mortality update ---
                if ($record->mortality > 0) {
                    $batch->total_mortality += $record->mortality;
                    $batch->historical_mortality += $record->mortality;
                    $batch->pond_mortality += $record->mortality;  // record pond mortality
                    $batch->mortality_rate = $batch->starting_flock > 0
                        ? ($batch->total_mortality / $batch->starting_flock) * 100
                        : 0;
                }

                // Culls and slaughter: weight removed but no mortality cost.
                // Slaughter cost allocation is handled separately (if needed).

                $batch->remaining_flock = $batch->current_count;
                $batch->save();
            }

            // Allocate cost for slaughtered birds (if needed)
            if ($record->slaughter > 0) {
                // We can call the old allocateCostForSlaughter if needed,
                // but with checkpoint approach, we don't use cost_allocated_so_far anymore.
                // The weight removal already happened above.
                // We could log it as a state change, but it's optional.
            }

            $batch->updateCachedMetrics();

            return $record;
        });

        return redirect()->route('poultry.batches.show', $record->batch)
            ->with('success', 'Flock record saved.');
    }

    public function edit(FlockRecord $flockRecord)
    {
        Gate::authorize('update', $flockRecord);
        return view('sectors.poultry.flock-records.edit', compact('flockRecord'));
    }

    public function update(FlockRecordRequest $request, FlockRecord $flockRecord)
    {
        Gate::authorize('update', $flockRecord);

        $data = $request->validated();

        DB::transaction(function () use ($flockRecord, $data) {
            $batch = $flockRecord->batch;

            // Reverse old changes first
            $oldReduction = ($flockRecord->mortality ?? 0) + ($flockRecord->culls ?? 0) + ($flockRecord->slaughter ?? 0);
            if ($oldReduction > 0) {
                $oldAvgWeight = $batch->current_average_weight; // approximate
                $oldWeightLost = $oldReduction * $oldAvgWeight;

                $batch->current_count += $oldReduction;
                $batch->current_weight_kg += $oldWeightLost;
                $batch->current_average_weight = $batch->current_count > 0
                    ? $batch->current_weight_kg / $batch->current_count
                    : 0;

                if ($flockRecord->mortality > 0) {
                    $batch->total_mortality -= $flockRecord->mortality;
                    $batch->historical_mortality -= $flockRecord->mortality;
                    $batch->pond_mortality -= $flockRecord->mortality;
                }
            }

            // Apply new changes
            $newReduction = ($data['mortality'] ?? 0) + ($data['culls'] ?? 0) + ($data['slaughter'] ?? 0);
            if ($newReduction > 0) {
                $newAvgWeight = $batch->current_average_weight;
                $newWeightLost = $newReduction * $newAvgWeight;

                $batch->current_count -= $newReduction;
                $batch->current_weight_kg -= $newWeightLost;
                $batch->current_average_weight = $batch->current_count > 0
                    ? $batch->current_weight_kg / $batch->current_count
                    : 0;

                if ($data['mortality'] > 0) {
                    $batch->total_mortality += $data['mortality'];
                    $batch->historical_mortality += $data['mortality'];
                    $batch->pond_mortality += $data['mortality'];
                }
            }

            $batch->mortality_rate = $batch->starting_flock > 0
                ? ($batch->total_mortality / $batch->starting_flock) * 100
                : 0;

            $batch->remaining_flock = $batch->current_count;
            $batch->save();

            // Update the record
            $flockRecord->update($data);

            $batch->updateCachedMetrics();
        });

        return redirect()->route('poultry.batches.show', $flockRecord->batch)
            ->with('success', 'Flock record updated.');
    }

    public function destroy(FlockRecord $flockRecord)
    {
        Gate::authorize('delete', $flockRecord);

        DB::transaction(function () use ($flockRecord) {
            $batch = $flockRecord->batch;

            // Reverse the effects
            $reduction = ($flockRecord->mortality ?? 0) + ($flockRecord->culls ?? 0) + ($flockRecord->slaughter ?? 0);
            if ($reduction > 0) {
                $avgWeight = $batch->current_average_weight;
                $weightLost = $reduction * $avgWeight;

                $batch->current_count += $reduction;
                $batch->current_weight_kg += $weightLost;
                $batch->current_average_weight = $batch->current_count > 0
                    ? $batch->current_weight_kg / $batch->current_count
                    : 0;

                if ($flockRecord->mortality > 0) {
                    $batch->total_mortality -= $flockRecord->mortality;
                    $batch->historical_mortality -= $flockRecord->mortality;
                    $batch->pond_mortality -= $flockRecord->mortality;
                }
            }

            $batch->mortality_rate = $batch->starting_flock > 0
                ? ($batch->total_mortality / $batch->starting_flock) * 100
                : 0;

            $batch->remaining_flock = $batch->current_count;
            $batch->save();

            $flockRecord->delete();
            $batch->updateCachedMetrics();
        });

        return redirect()->back()->with('success', 'Flock record deleted.');
    }
}