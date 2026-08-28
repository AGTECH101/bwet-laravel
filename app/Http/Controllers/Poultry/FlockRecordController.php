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
            // Create the record
            $record = FlockRecord::create($data);

            // Adjust remaining flock: subtract mortality, culls, and slaughter
            $reduction = ($record->mortality ?? 0) + ($record->culls ?? 0) + ($record->slaughter ?? 0);
            $batch->remaining_flock = max(0, $batch->remaining_flock - $reduction);
            $batch->save();

            // Allocate cost for slaughtered birds
            if ($record->slaughter > 0) {
                $oldRemaining = $batch->remaining_flock + $record->slaughter; // before slaughter
                $oldTotalInvestment = $batch->calculateTotalInvestment();
                $allocated = $batch->allocateCostForSlaughter($record->slaughter, $oldRemaining, $oldTotalInvestment);
                $record->allocated_cost = $allocated;
                $record->save();
            }

            $batch->updateCachedMetrics();

            return $record;
        });

        return redirect()->route('poultry.batches.show', $record->batch)
            ->with('success', 'Flock record saved. Remaining flock updated and slaughter costs allocated.');
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

            // Calculate old and new reductions
            $oldReduction = ($flockRecord->mortality ?? 0) + ($flockRecord->culls ?? 0) + ($flockRecord->slaughter ?? 0);
            $newReduction = ($data['mortality'] ?? 0) + ($data['culls'] ?? 0) + ($data['slaughter'] ?? 0);
            $diff = $newReduction - $oldReduction;

            // Adjust remaining flock
            $batch->remaining_flock = max(0, $batch->remaining_flock - $diff);
            $batch->save();

            // Remove old cost allocation
            if ($flockRecord->slaughter > 0 && $flockRecord->allocated_cost > 0) {
                $batch->cost_allocated_so_far = max(0, $batch->cost_allocated_so_far - $flockRecord->allocated_cost);
                $batch->save();
            }

            // Update the record
            $flockRecord->update($data);

            // Apply new cost allocation
            if ($flockRecord->slaughter > 0) {
                $batch->refresh();
                $oldRemaining = $batch->remaining_flock + $flockRecord->slaughter; // before slaughter
                $oldTotalInvestment = $batch->calculateTotalInvestment();
                $allocated = $batch->allocateCostForSlaughter($flockRecord->slaughter, $oldRemaining, $oldTotalInvestment);
                $flockRecord->allocated_cost = $allocated;
                $flockRecord->save();
            } else {
                $flockRecord->allocated_cost = 0;
                $flockRecord->save();
            }

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

            // Add back the reduction to remaining flock
            $reduction = ($flockRecord->mortality ?? 0) + ($flockRecord->culls ?? 0) + ($flockRecord->slaughter ?? 0);
            $batch->remaining_flock += $reduction;
            $batch->save();

            // Reverse cost allocation
            if ($flockRecord->slaughter > 0 && $flockRecord->allocated_cost > 0) {
                $batch->cost_allocated_so_far = max(0, $batch->cost_allocated_so_far - $flockRecord->allocated_cost);
                $batch->save();
            }

            $flockRecord->delete();
            $batch->updateCachedMetrics();
        });

        return redirect()->back()->with('success', 'Flock record deleted. Remaining flock restored and cost allocation reversed.');
    }
}